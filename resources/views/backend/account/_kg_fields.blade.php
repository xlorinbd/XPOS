@php
    $kgLists = \Illuminate\Support\Facades\Cache::remember('kg_account_form_lists', 60, function () {
        return [
            'warehouses' => \App\Models\Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => \App\Models\User::where('is_active', true)->where('is_deleted', false)->where('role_id', '!=', 5)->orderBy('name')->get(['id', 'name']),
        ];
    });
    $kgP = $prefix ?? 'add';
@endphp
<div class="form-group">
    <label>Account type</label>
    <select name="type" class="form-control kg-account-type" data-scope="{{ $kgP }}">
        @foreach(array_keys(\App\Models\Account::KINDS) as $kind)
        <option value="{{ $kind }}">{{ $kind }}</option>
        @endforeach
    </select>
    <small class="text-muted">Transfers are only offered between the right kinds of account.</small>
</div>
<div class="form-group kg-branch-field" data-scope="{{ $kgP }}">
    <label>Branch / Warehouse</label>
    <select name="warehouse_id" class="form-control">
        <option value="">None (company level)</option>
        @foreach($kgLists['warehouses'] as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
    </select>
</div>
<div class="form-group kg-owner-field" data-scope="{{ $kgP }}" style="display:none">
    <label>Staff member holding this wallet</label>
    <select name="owner_user_id" class="form-control">
        <option value="">Select staff...</option>
        @foreach($kgLists['users'] as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
    </select>
</div>
