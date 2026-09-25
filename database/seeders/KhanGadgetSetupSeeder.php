<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Biller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Lookup;
use App\Services\ProcessorNormalizer;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Applies the Khan Gadget setup data (setup-data/khan_gadget_setup.json).
 * Idempotent: safe to run more than once.
 *   php artisan db:seed --class=KhanGadgetSetupSeeder
 */
class KhanGadgetSetupSeeder extends Seeder
{
    private array $setup;

    public function run(): void
    {
        $path = base_path('setup-data/khan_gadget_setup.json');
        $this->setup = json_decode(File::get($path), true);

        $this->currencyAndCompany();
        $this->rolesAndPermissions();
        $this->branches();
        $this->catalog();
        $this->lists();
        $this->accounts();
        $this->posDefaults();
        \App\Models\HrmSetting::current();

        if (app()->environment('local')) {
            $this->dummyUsers();
        }

        cache()->forget('currency');
        cache()->forget('general_setting');
        cache()->forget('warehouse_list');
        cache()->forget('permissions');
        cache()->forget('user_role');
        cache()->forget('category_list');
        foreach (Role::pluck('id') as $roleId) {
            cache()->forget('role_has_permissions_list' . $roleId);
        }
    }

    private function currencyAndCompany(): void
    {
        $bdt = Currency::where('code', 'BDT')->first() ?? Currency::create([
            'name' => 'Bangladeshi Taka', 'code' => 'BDT', 'exchange_rate' => 1,
        ]);
        $bdt->update(['symbol' => '৳']);

        $company = $this->setup['company'];

        $logoSrc = base_path('setup-data/' . $company['logo_file']);
        $logoName = $company['logo_file'];
        if (File::exists($logoSrc)) {
            File::ensureDirectoryExists(public_path('logo'));
            File::copy($logoSrc, public_path('logo/' . $logoName));
        }

        $gs = DB::table('general_settings')->orderByDesc('id')->first();
        if ($gs) {
            DB::table('general_settings')->where('id', $gs->id)->update([
                'site_title' => $company['name'],
                'company_name' => $company['name'],
                'site_logo' => $logoName,
                'favicon' => $logoName,
                'currency' => $bdt->id,
                'currency_position' => 'prefix',
            ]);
        }

        $biller = Biller::where('name', $company['name'])->first();
        $billerData = [
            'name' => $company['name'],
            'company_name' => $company['name'],
            'email' => $company['email'],
            'phone_number' => $company['phone'],
            'address' => $company['address'],
            'city' => 'Rajshahi',
            'is_active' => true,
        ];
        if ($biller) {
            $biller->update($billerData);
        } else {
            $biller = Biller::create($billerData);
        }
        Biller::where('id', '!=', $biller->id)->where('name', 'Test Biller')->update(['is_active' => false]);
    }

    private function rolesAndPermissions(): void
    {
        $costPerm = Permission::firstOrCreate(['name' => 'view-cost-profit', 'guard_name' => 'web']);
        $acceptPerm = Permission::firstOrCreate(['name' => 'transfer-accept', 'guard_name' => 'web']);
        $all = Permission::pluck('name')->all();

        $managerExclude = ['empty_database', 'backup_database', 'role_permission', 'addons', 'payment_gateway_setting', 'mail_setting', 'sms_setting'];

        $definitions = [
            1 => ['name' => 'Admin', 'description' => 'Full access to everything.', 'perms' => $all],
            2 => ['name' => 'Manager', 'description' => 'Manages all branches. Can see purchase price and profit.', 'perms' => array_values(array_diff($all, $managerExclude))],
        ];

        foreach ($definitions as $id => $d) {
            $role = Role::find($id) ?? new Role(['id' => $id]);
            $role->name = $d['name'];
            $role->guard_name = 'web';
            $role->description = $d['description'];
            $role->is_active = true;
            $role->save();
            if ($id === 1 || $role->permissions()->count() < 5) {
                $role->syncPermissions($d['perms']);
            }
        }

        $sidebar = ['sidebar_product', 'sidebar_purchase', 'sidebar_sale', 'sidebar_quotation', 'sidebar_transfer', 'sidebar_expense', 'sidebar_income', 'sidebar_accounting', 'sidebar_hrm', 'sidebar_people', 'sidebar_reports', 'sidebar_settings'];

        $others = [
            'Branch Manager' => [
                'id' => 3,
                'description' => "Runs one branch: stock, sales, transfers, expenses. Cannot see purchase price or profit.",
                'perms' => array_merge(
                    ['products-index', 'sales-index', 'sales-add', 'sales-edit', 'quotes-index', 'quotes-add', 'quotes-edit',
                     'transfers-index', 'transfers-add', 'transfers-edit', 'returns-index', 'returns-add', 'returns-edit',
                     'customers-index', 'customers-add', 'customers-edit', 'suppliers-index',
                     'expenses-index', 'expenses-add', 'expenses-edit', 'sale-payment-index', 'sale-payment-add',
                     'product-report', 'sale-report', 'daily-sale', 'monthly-sale', 'warehouse-stock-report', 'product-qty-alert',
                     'today_sale', 'print_barcode', 'stock_count', 'handle_discount', 'cart-product-update', 'delivery'],
                    ['sidebar_product', 'sidebar_sale', 'sidebar_quotation', 'sidebar_transfer', 'sidebar_expense', 'sidebar_people', 'sidebar_reports']
                ),
            ],
            'Seller' => [
                'id' => 6,
                'description' => 'Sells from POS. Cannot see purchase price or profit.',
                'perms' => ['products-index', 'sales-index', 'sales-add', 'quotes-index', 'quotes-add', 'customers-index', 'customers-add',
                            'sale-payment-index', 'sale-payment-add', 'cart-product-update', 'handle_discount', 'today_sale',
                            'sidebar_product', 'sidebar_sale', 'sidebar_quotation', 'sidebar_people'],
            ],
            'Accountant' => [
                'id' => 7,
                'description' => 'Accounts, payments, expenses. Cannot see purchase price or profit.',
                'perms' => ['account-index', 'account-statement', 'balance-sheet', 'money-transfer', 'account-selection',
                            'expenses-index', 'expenses-add', 'expenses-edit', 'incomes-index', 'incomes-add', 'incomes-edit',
                            'sale-payment-index', 'sale-payment-add', 'purchase-payment-index', 'purchase-payment-add',
                            'payment-report', 'cash_flow', 'sales-index', 'purchases-index', 'customers-index', 'suppliers-index',
                            'sidebar_accounting', 'sidebar_expense', 'sidebar_income', 'sidebar_reports', 'sidebar_people'],
            ],
        ];

        foreach ($others as $name => $d) {
            $role = Role::where('name', $name)->first() ?? Role::where('id', $d['id'])->first() ?? new Role();
            if (!$role->exists) {
                $role->id = $d['id'];
            }
            $role->name = $name;
            $role->guard_name = 'web';
            $role->description = $d['description'];
            $role->is_active = true;
            $role->save();
            if ($role->permissions()->count() < 5) {
                $role->syncPermissions($d['perms']);
            }
        }

        // Newly introduced permission: hand it out once, never re-add it after an admin removed it.
        if ($acceptPerm->wasRecentlyCreated) {
            foreach (['Manager', 'Branch Manager', 'Accountant'] as $roleName) {
                Role::where('name', $roleName)->first()?->givePermissionTo('transfer-accept');
            }
            foreach (['Branch Manager', 'Accountant'] as $roleName) {
                Role::where('name', $roleName)->first()?->givePermissionTo('money-transfer');
            }
        }

        // Existing generic "staff" role becomes "Staff" with minimal access.
        $staff = Role::find(4);
        if ($staff) {
            $staff->name = 'Staff';
            $staff->description = 'General staff with minimal access.';
            $staff->save();
            if ($staff->permissions()->count() < 5) {
                $staff->syncPermissions(['products-index', 'sales-index', 'sidebar_product', 'sidebar_sale']);
            }
        }
    }

    private function branches(): void
    {
        $products = Product::pluck('id');

        $rows = [];
        $w = $this->setup['warehouse'];
        $rows[] = ['name' => $w['name'], 'type' => 'warehouse', 'phone' => $w['phone'], 'address' => $w['address'], 'email' => null];
        foreach ($this->setup['branches'] as $b) {
            $rows[] = ['name' => $b['name'], 'type' => $b['type'], 'phone' => $b['phone'], 'address' => $b['address'], 'email' => null];
        }

        foreach ($rows as $row) {
            $wh = Warehouse::where('name', $row['name'])->first();
            if ($wh) {
                if (empty($wh->type)) {
                    $wh->update(['type' => $row['type']]);
                }
            } else {
                $wh = Warehouse::create($row + ['is_active' => true]);
            }
            $existing = array_map('intval', Product_Warehouse::where('warehouse_id', $wh->id)->pluck('product_id')->all());
            foreach ($products as $pid) {
                if (!in_array((int) $pid, $existing, true)) {
                    Product_Warehouse::create(['product_id' => $pid, 'warehouse_id' => $wh->id, 'qty' => 0]);
                }
            }
        }

        // Hide the developer test warehouses (kept in the database, just inactive).
        Warehouse::where(function ($q) {
            $q->where('name', 'Test Shop')
              ->orWhere('name', 'like', 'Uttara Branch (Test%')
              ->orWhere('name', 'like', 'Mirpur 10 Branch - %');
        })->update(['is_active' => false]);
    }

    private function catalog(): void
    {
        $cat = $this->setup['catalog'];

        foreach ($cat['brands'] as $title) {
            Brand::firstOrCreate(['title' => $title], ['is_active' => true]);
        }

        foreach ($cat['categories'] as $parentName => $children) {
            $parent = Category::firstOrCreate(['name' => $parentName, 'parent_id' => null], ['is_active' => true]);
            foreach ($children as $childName) {
                Category::firstOrCreate(['name' => $childName, 'parent_id' => $parent->id], ['is_active' => true]);
            }
        }
    }

    private function lists(): void
    {
        $lists = $this->setup['lists'] ?? [];

        foreach ($lists['processors'] ?? [] as $name) {
            $formatted = ProcessorNormalizer::normalize($name)['normalized'];
            if (!Lookup::ofType('processor')->where('match_key', Lookup::processorKey($formatted))->exists()) {
                Lookup::create(['type' => 'processor', 'name' => $formatted, 'is_active' => true]);
            }
        }

        foreach ($lists['cargo_companies'] ?? [] as $name) {
            Lookup::firstOrCreate(['type' => 'cargo_company', 'name' => $name], ['is_active' => true]);
        }

        if (!Lookup::ofType('damage_type')->exists()) {
            foreach (['Dead on Arrival (DOA)', 'Display / Screen Defect', 'Physical / Body Damage', 'Liquid / Moisture Damage', 'Motherboard / Power Failure', 'Customer Return (Defective)', 'Other Defect'] as $name) {
                Lookup::create(['type' => 'damage_type', 'name' => $name, 'is_active' => true]);
            }
        }

        foreach ($lists['charger_models'] ?? [] as $name) {
            Lookup::firstOrCreate(['type' => 'charger_model', 'name' => $name], ['is_active' => true]);
        }
    }

    private function accounts(): void
    {
        $cfg = $this->setup['accounts'] ?? [];
        $make = function (string $no, string $name, string $type, ?int $warehouseId = null) {
            Account::firstOrCreate(['account_no' => $no], [
                'name' => $name, 'type' => $type, 'warehouse_id' => $warehouseId, 'initial_balance' => 0, 'total_balance' => 0,
                'is_active' => true, 'is_default' => false, 'is_payment' => true,
                'note' => 'Setup data (placeholder, adjust the opening balance)',
            ]);
        };

        foreach ($cfg['main'] ?? [] as $i => $name) {
            $make('KG-MAIN-' . ($i + 1), $name, stripos($name, 'mobile') !== false ? 'Main Mobile Banking' : 'Main Bank');
        }
        foreach ($cfg['payment_gateway_pending_confirmation'] ?? [] as $i => $name) {
            $make('KG-GW-' . ($i + 1), $name, 'Payment Gateway');
        }

        foreach (Warehouse::where('is_active', true)->whereIn('type', ['branch', 'service_center', 'warehouse'])->orderBy('id')->get() as $wh) {
            if ($wh->type === 'warehouse') {
                $make('KG-WH-' . $wh->id . '-CASH', $wh->name . ' - Cash', 'Warehouse Cash', $wh->id);
                continue;
            }
            $make('KG-BR-' . $wh->id . '-CASH', $wh->name . ' - Cash', 'Branch Cash', $wh->id);
            $make('KG-BR-' . $wh->id . '-BANK', $wh->name . ' - Bank', 'Branch Bank', $wh->id);
            $make('KG-BR-' . $wh->id . '-WALLET', $wh->name . ' - Mobile Wallet', 'Branch Mobile Wallet', $wh->id);
        }

        // the one older generic account keeps working as a company-level account
        Account::whereNull('warehouse_id')->where('type', 'Bank Account')->update(['type' => 'Bank Account']);
    }

    /**
     * POS defaults for Khan Gadget: no on-screen keyboard, the payment methods the shops really use,
     * A4 invoice. Only replaces the factory defaults, so later changes in Settings are kept.
     */
    private function posDefaults(): void
    {
        $pos = DB::table('pos_setting')->first();
        if (!$pos) {
            return;
        }
        $changes = [];
        if ($pos->payment_options === 'cash,card,cheque,gift_card,deposit,paypal') {
            $changes['payment_options'] = 'cash,card,Bank Transfer,Mobile Banking';
            $changes['keybord_active'] = 0;
            $changes['invoice_option'] = 'A4';
        }
        if ($changes) {
            DB::table('pos_setting')->where('id', $pos->id)->update($changes);
            cache()->forget('pos_setting');
        }
    }

    private function dummyUsers(): void
    {
        $biller = Biller::where('name', $this->setup['company']['name'])->first();
        $branch = Warehouse::where('name', 'Rajshahi Branch 1')->first();
        $second = Warehouse::where('name', 'Rajshahi Branch 2')->first();
        $roleId = fn(string $n) => Role::where('name', $n)->value('id');

        $dummies = [
            ['Dummy Manager', 'dummy.manager@example.com', 'Manager', []],
            ['Dummy Branch Manager', 'dummy.branchmanager@example.com', 'Branch Manager', [$branch, $second]],
            ['Dummy Seller', 'dummy.seller@example.com', 'Seller', [$branch]],
            ['Dummy Accountant', 'dummy.accountant@example.com', 'Accountant', [$branch]],
        ];

        foreach ($dummies as [$name, $email, $role, $branches]) {
            $branches = array_values(array_filter($branches));
            $user = User::where('email', $email)->first();
            $data = [
                'name' => $name,
                'email' => $email,
                'phone' => '01XXXXXXXXX',
                'company_name' => $this->setup['company']['name'],
                'role_id' => $roleId($role),
                'biller_id' => $roleId($role) > 2 ? $biller?->id : null,
                'warehouse_id' => $branches[0]->id ?? null,
                'is_active' => true,
                'is_deleted' => false,
            ];
            if ($user) {
                $user->update($data);
            } else {
                $user = User::create($data + ['password' => Hash::make('Dummy@12345')]);
            }
            $user->branches()->sync(array_map(fn($b) => $b->id, $branches));
        }
    }
}
