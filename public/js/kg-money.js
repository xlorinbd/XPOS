(function (w) {
    // Indian (lakh/crore) grouping, currency symbol prefix, decimals only when present: 4,00,000 / 4,00,000.50
    w.kgAmount = function (n, decimals) {
        decimals = (decimals === undefined) ? 2 : decimals;
        n = parseFloat(n);
        if (isNaN(n)) { n = 0; }
        var neg = n < 0;
        var fixed = Math.abs(n).toFixed(decimals);
        var parts = fixed.split('.');
        var int = parts[0], frac = parts[1] || '';
        if (frac && parseInt(frac, 10) === 0) { frac = ''; }
        if (int.length > 3) {
            var last3 = int.slice(-3), rest = int.slice(0, -3);
            int = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + last3;
        }
        return (neg ? '-' : '') + int + (frac ? '.' + frac : '');
    };
    w.kgMoney = function (n, decimals) {
        n = parseFloat(n);
        if (isNaN(n)) { n = 0; }
        return (n < 0 ? '-' : '') + (w.KG_CURRENCY_SYMBOL || '\u09F3') + w.kgAmount(Math.abs(n), decimals);
    };
})(window);
