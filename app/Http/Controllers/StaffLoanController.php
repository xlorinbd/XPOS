<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Employee;
use App\Models\HrmSetting;
use App\Models\MailSetting;
use App\Models\Payroll;
use App\Models\StaffLoan;
use App\Models\User;
use App\Services\AccountLedger;
use App\Services\KgNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

/**
 * Staff loan / advance.
 *  - the staff member applies for themselves (my-loan); before the request is saved a one-time code is sent to
 *    the e-mail kept on their staff record, so nobody can apply in someone else's name;
 *  - management approves (or rejects) by hand and chooses the account the money is paid from;
 *  - approved instalments are taken from the monthly salary automatically (SalaryService / SalarySheetController).
 */
class StaffLoanController extends Controller
{
    use \App\Traits\MailInfo;

    private const OTP_MINUTES = 10;
    private const OTP_MAX_TRIES = 5;

    // ------------------------------------------------------------ staff side

    private function myEmployee(): ?Employee
    {
        return Employee::where('user_id', Auth::id())->where('is_active', true)->first();
    }

    public function mine()
    {
        $employee = $this->myEmployee();
        $loans = $employee ? StaffLoan::where('employee_id', $employee->id)->orderByDesc('id')->get() : collect();
        $maskedEmail = $employee ? $this->mask($employee->email) : null;
        return view('backend.staff_loan.mine', compact('employee', 'loans', 'maskedEmail'));
    }

    public function sendOtp(Request $request)
    {
        $employee = $this->myEmployee();
        if (!$employee) {
            return response()->json(['error' => 'Your login is not linked to a staff record. Ask the admin to link it.'], 422);
        }
        if (!filter_var($employee->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'No e-mail address is saved on your staff record. Ask the admin to add it.'], 422);
        }
        if (HrmSetting::current()->otp_channel !== 'email') {
            return response()->json(['error' => 'The SMS gateway is not connected yet. The code can only be sent by e-mail for now.'], 422);
        }
        // kept in the database, not the cache, so it works whatever cache driver the hosting uses
        $previous = DB::table('staff_loan_otps')->where('employee_id', $employee->id)->first();
        if ($previous && now()->diffInSeconds($previous->sent_at, false) > -60) {
            return response()->json(['error' => 'A code was sent a moment ago. Wait a minute before asking for another one.'], 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('staff_loan_otps')->updateOrInsert(['employee_id' => $employee->id], [
            'code_hash' => hash('sha256', $code . config('app.key')), 'tries' => 0,
            'expires_at' => now()->addMinutes(self::OTP_MINUTES), 'sent_at' => now(), 'updated_at' => now(), 'created_at' => now(),
        ]);

        try {
            $this->configureMail();
            $company = optional(DB::table('general_settings')->first())->company_name ?? 'Khan Gadget';
            Mail::raw("Your verification code for the staff loan request is {$code}.\nIt is valid for " . self::OTP_MINUTES . " minutes. If you did not ask for it, ignore this message and tell the admin.\n\n{$company}",
                function ($m) use ($employee, $company) {
                    $m->to($employee->email)->subject('Loan request verification code - ' . $company);
                });
        } catch (\Throwable $e) {
            DB::table('staff_loan_otps')->where('employee_id', $employee->id)->delete();
            \Log::warning('Loan OTP mail failed: ' . $e->getMessage());
            return response()->json(['error' => 'The code could not be e-mailed. Check the mail settings (Settings > Mail Setting).'], 500);
        }

        return response()->json(['success' => true, 'message' => 'A 6 digit code was sent to ' . $this->mask($employee->email) . '.']);
    }

    public function apply(Request $request)
    {
        $employee = $this->myEmployee();
        if (!$employee) {
            return redirect()->back()->with('not_permitted', 'Your login is not linked to a staff record.');
        }
        $data = $request->validate([
            'kind' => 'required|in:loan,advance',
            'amount' => 'required|numeric|min:1',
            'installments' => 'nullable|integer|min:1|max:36',
            'reason' => 'required|string|max:255',
            'otp' => 'required|digits:6',
        ]);

        $otp = DB::table('staff_loan_otps')->where('employee_id', $employee->id)->first();
        if (!$otp || now()->gt($otp->expires_at)) {
            return redirect()->back()->withInput()->with('not_permitted', 'The code has expired. Ask for a new code.');
        }
        if ($otp->tries >= self::OTP_MAX_TRIES) {
            DB::table('staff_loan_otps')->where('employee_id', $employee->id)->delete();
            return redirect()->back()->withInput()->with('not_permitted', 'Too many wrong codes. Ask for a new code.');
        }
        if (!hash_equals($otp->code_hash, hash('sha256', $data['otp'] . config('app.key')))) {
            DB::table('staff_loan_otps')->where('employee_id', $employee->id)->increment('tries');
            return redirect()->back()->withInput()->with('not_permitted', 'The code is not correct.');
        }
        DB::table('staff_loan_otps')->where('employee_id', $employee->id)->delete(); // a code works only once

        $loan = StaffLoan::create([
            'employee_id' => $employee->id,
            'kind' => $data['kind'],
            'amount' => $data['amount'],
            'installments' => $data['kind'] === 'advance' ? 1 : (int) ($data['installments'] ?? 1),
            'reason' => $data['reason'],
            'status' => 'pending',
            'otp_verified_at' => now(),
            'requested_by' => Auth::id(),
        ]);

        KgNotifier::toRoles(['Admin', 'Manager'],
            ucfirst($loan->kind) . ' request from ' . $employee->name . ': ' . money($loan->amount) . ' (' . $loan->reason . '). Identity confirmed by e-mail code.',
            '/staff-loans', 'request', null, Auth::id());

        return redirect()->route('loan.mine')->with('message', 'Your request was sent for approval.');
    }

    // ------------------------------------------------------------ management side

    private function canManage(): bool
    {
        $role = Role::find(Auth::user()->role_id);
        return $role && $role->hasPermissionTo('payroll');
    }

    public function index(Request $request)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $tab = $request->input('tab', 'pending');
        $loans = StaffLoan::with('employee')->when($tab !== 'all', fn($q) => $q->where('status', $tab === 'running' ? 'approved' : 'pending'))
            ->orderByDesc('id')->get();
        $accounts = Account::where('is_active', true)->where('type', '!=', 'Staff Wallet')->orderBy('name')->get();
        $ledger = app(AccountLedger::class);
        $balances = $accounts->mapWithKeys(fn($a) => [$a->id => $ledger->balance($a)]);
        return view('backend.staff_loan.index', compact('loans', 'tab', 'accounts', 'balances'));
    }

    public function approve(Request $request, $id)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $data = $request->validate([
            'pay_account_id' => 'required|integer|exists:accounts,id',
            'installments' => 'required|integer|min:1|max:36',
            'start_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'decision_note' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($id, $data) {
                $loan = StaffLoan::lockForUpdate()->findOrFail($id);
                if ($loan->status !== 'pending') {
                    throw new \RuntimeException('This request was already decided.');
                }
                $account = Account::lockForUpdate()->findOrFail($data['pay_account_id']);
                if (!$account->is_active || $account->kind === 'staff') {
                    throw new \RuntimeException('Choose a branch cash / bank account to pay from.');
                }
                if (app(AccountLedger::class)->balance($account) + 0.0001 < (float) $loan->amount) {
                    throw new \RuntimeException($account->name . ' does not have enough balance (' . money(app(AccountLedger::class)->balance($account)) . ').');
                }
                $employee = Employee::findOrFail($loan->employee_id);
                $installments = $loan->kind === 'advance' ? 1 : (int) $data['installments'];

                // the payout leaves the account now; it comes back through the salary instalments
                $payout = Payroll::create([
                    'reference_no' => 'loan-' . date('Ymd') . '-' . $loan->id,
                    'employee_id' => $employee->id,
                    'account_id' => $account->id,
                    'user_id' => Auth::id(),
                    'amount' => $loan->amount,
                    'paying_method' => $account->type,
                    'note' => ucfirst($loan->kind) . ' #' . $loan->id . ' paid to ' . $employee->name,
                    'status' => 'loan',
                    'amount_array' => json_encode(['loan' => (float) $loan->amount]),
                    'month' => date('Y-m'),
                ]);

                $loan->update([
                    'status' => 'approved',
                    'installments' => $installments,
                    'installment_amount' => ceil(((float) $loan->amount / $installments) * 100) / 100,
                    'start_month' => $data['start_month'],
                    'remaining' => $loan->amount,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'pay_account_id' => $account->id,
                    'decision_note' => $data['decision_note'] ?? null,
                ]);

                if ($employee->user_id && ($u = User::find($employee->user_id))) {
                    KgNotifier::toUsers([$u], ucfirst($loan->kind) . ' of ' . money($loan->amount) . ' approved. It is deducted from your salary in ' . $installments . ' instalment(s) from ' . $data['start_month'] . '.', '/my-loan', 'request');
                }
            });
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('not_permitted', $e->getMessage());
        }

        return redirect()->back()->with('message', 'Approved and paid. The instalments will come out of the salary automatically.');
    }

    public function reject(Request $request, $id)
    {
        if (!$this->canManage()) {
            return redirect()->back()->with('not_permitted', __('db.Sorry! You are not allowed to access this module'));
        }
        $request->validate(['decision_note' => 'required|string|max:255']);
        $loan = StaffLoan::with('employee')->findOrFail($id);
        if ($loan->status !== 'pending') {
            return redirect()->back()->with('not_permitted', 'This request was already decided.');
        }
        $loan->update(['status' => 'rejected', 'approved_by' => Auth::id(), 'approved_at' => now(), 'decision_note' => $request->decision_note]);
        if ($loan->employee && $loan->employee->user_id && ($u = User::find($loan->employee->user_id))) {
            KgNotifier::toUsers([$u], ucfirst($loan->kind) . ' request of ' . money($loan->amount) . ' was not approved: ' . $request->decision_note, '/my-loan', 'request');
        }
        return redirect()->back()->with('message', 'Request rejected.');
    }

    // ------------------------------------------------------------ helpers

    private function mask(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '';
        }
        [$name, $domain] = explode('@', $email, 2);
        return mb_substr($name, 0, 1) . str_repeat('*', max(2, mb_strlen($name) - 1)) . '@' . $domain;
    }

    /** Use the SMTP details saved under Settings > Mail Setting; otherwise the server's own default mailer. */
    private function configureMail(): void
    {
        $s = MailSetting::latest()->first();
        if (!$s) {
            return;
        }
        $this->setMailInfo($s); // this package keeps its mail settings in the old-style config keys
        Mail::purge();
    }
}
