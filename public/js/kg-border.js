/*
 * Border price warning: when the server answers needs_border_reason the page shows this dialog,
 * the seller types a reason and the request is sent again with that reason.
 * usage: kgBorderReason(lines, function (reason) { ... }, function () { ...cancelled... });
 */
window.kgBorderReason = function (lines, onDone, onCancel) {
    var $ = window.jQuery;
    $('#kgBorderModal').remove();
    var list = (lines || []).map(function (l) { return '<li>' + $('<div>').text(l).html() + '</li>'; }).join('');
    $('body').append(
        '<div class="modal fade" id="kgBorderModal" tabindex="-1" role="dialog" data-backdrop="static" style="z-index:2000">' +
        '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">' +
        '<div class="modal-header"><h5 class="modal-title">Price is below the border price</h5></div>' +
        '<div class="modal-body">' +
        '<ul style="padding-left:18px;font-size:13px">' + list + '</ul>' +
        '<label style="font-weight:600">Reason for selling below the border price *</label>' +
        '<textarea id="kgBorderReason" class="form-control" rows="2" maxlength="500" placeholder="e.g. Old stock clearance, approved by MD"></textarea>' +
        '<small id="kgBorderErr" class="text-danger" style="display:none">Please write a reason (at least 3 characters).</small>' +
        '</div>' +
        '<div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="kgBorderCancel">Cancel</button>' +
        '<button type="button" class="btn btn-dark" id="kgBorderOk">Continue with this price</button></div>' +
        '</div></div></div>'
    );
    var $m = $('#kgBorderModal');
    $m.modal('show');
    $m.on('shown.bs.modal', function () { $('#kgBorderReason').trigger('focus'); });
    $('#kgBorderOk').on('click', function () {
        var reason = $.trim($('#kgBorderReason').val());
        if (reason.length < 3) { $('#kgBorderErr').show(); return; }
        $m.modal('hide');
        onDone(reason);
    });
    $('#kgBorderCancel').on('click', function () {
        $m.modal('hide');
        if (onCancel) onCancel();
    });
};
