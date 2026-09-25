/*
 * Payment account picker shared by pre-orders and other simple forms.
 * kgFillAccounts($select, accounts, warehouseId, method) lists the accounts usable in that branch
 * (company accounts + the branch's own) and pre-selects the natural one for the payment method.
 */
window.kgFillAccounts = function ($select, accounts, warehouseId, method) {
    var wh = String(warehouseId || '');
    var m = String(method || '').toLowerCase();
    var want = m === 'cash' ? ['Branch Cash', 'Warehouse Cash']
        : (m === 'card' || m === 'credit card') ? ['Payment Gateway']
        : m.indexOf('bank') !== -1 ? ['Branch Bank', 'Main Bank']
        : ['Branch Mobile Wallet', 'Main Mobile Banking'];
    var usable = (accounts || []).filter(function (a) {
        return a.type !== 'Staff Wallet' && (!a.warehouse_id || String(a.warehouse_id) === wh);
    });
    var pick = null;
    want.forEach(function (t) {
        if (pick) return;
        usable.forEach(function (a) { if (!pick && a.type === t) pick = a.id; });
    });
    var html = '';
    usable.forEach(function (a) {
        html += '<option value="' + a.id + '"' + (a.id === pick ? ' selected' : '') + '>' + $('<div>').text(a.name).html() + '</option>';
    });
    $select.html(html);
};
