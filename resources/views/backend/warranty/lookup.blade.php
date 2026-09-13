@extends('backend.layout.main')

@section('content')
<div class="container-fluid pt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header Card -->
                <div class="card-header bg-primary text-white p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="font-weight-bold mb-1"><i class="fa fa-shield mr-2"></i> Warranty & Guarantee Verification</h3>
                            <p class="mb-0 text-white-50" style="font-size:14px;">Instant serial number scan & policy validity lookup for Khan Gadget POS</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <span class="badge badge-light px-3 py-2 text-dark font-weight-bold" style="font-size:12px;">
                                <i class="fa fa-lock text-success mr-1"></i> Staff Authenticated
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Search Input Bar -->
                <div class="card-body p-4 bg-white">
                    <form id="warrantyLookupForm" onsubmit="return false;">
                        @csrf
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fa fa-barcode fa-lg text-primary"></i></span>
                            </div>
                            <input type="text" id="serialSearchInput" class="form-control border-left-0 font-weight-bold" placeholder="Scan or type Serial Number (e.g. SN-...) or Invoice Reference..." autofocus autocomplete="off" style="font-size:16px;">
                            <div class="input-group-append">
                                <button type="submit" id="btnSearch" class="btn btn-primary px-4 font-weight-bold">
                                    <i class="fa fa-search mr-1"></i> Verify Policy
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Alert message container -->
                    <div id="alertBox" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- Verification Result Card (Dynamic) -->
            <div id="resultCard" class="card shadow-sm border-0 d-none mb-4" style="border-radius:12px;">
                <div class="card-header bg-light py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark"><i class="fa fa-check-circle text-success mr-2"></i> Verified Device Record</h5>
                    <span id="resSerialBadge" class="badge badge-dark px-3 py-2" style="font-size:13px; font-family:monospace;"></span>
                </div>
                <div class="card-body p-4">
                    <!-- Product Title & Specs -->
                    <div class="border-bottom pb-3 mb-4">
                        <div class="d-flex align-items-center mb-1">
                            <span id="resConditionBadge" class="badge badge-secondary mr-2 px-2 py-1" style="font-size:11px;"></span>
                            <h4 id="resProductName" class="font-weight-bold mb-0 text-primary"></h4>
                        </div>
                        <div id="resSpecsLine" class="text-muted small mt-1 font-weight-bold"><i class="fa fa-laptop text-info mr-1"></i> </div>
                    </div>

                    <!-- Guarantee & Warranty Policies Grid -->
                    <div class="row mb-4">
                        <!-- Guarantee Box -->
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card border h-100 shadow-sm" style="border-radius:10px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="font-weight-bold text-uppercase text-secondary mb-0" style="font-size:12px;">
                                            <i class="fa fa-refresh text-warning mr-1"></i> Replacement Guarantee
                                        </h6>
                                        <span id="resGuaranteeStatusBadge" class="badge badge-pill font-weight-bold px-3 py-1"></span>
                                    </div>
                                    <h5 id="resGuaranteeDuration" class="font-weight-bold mb-1 text-dark"></h5>
                                    <div class="small text-muted mb-1">
                                        <strong>Expires On:</strong> <span id="resGuaranteeExpire"></span>
                                    </div>
                                    <div id="resGuaranteeRemaining" class="small font-weight-bold"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Warranty Box -->
                        <div class="col-md-6">
                            <div class="card border h-100 shadow-sm" style="border-radius:10px;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="font-weight-bold text-uppercase text-secondary mb-0" style="font-size:12px;">
                                            <i class="fa fa-wrench text-info mr-1"></i> Service Warranty
                                        </h6>
                                        <span id="resWarrantyStatusBadge" class="badge badge-pill font-weight-bold px-3 py-1"></span>
                                    </div>
                                    <h5 id="resWarrantyDuration" class="font-weight-bold mb-1 text-dark"></h5>
                                    <div class="small text-muted mb-1">
                                        <strong>Expires On:</strong> <span id="resWarrantyExpire"></span>
                                    </div>
                                    <div id="resWarrantyRemaining" class="small font-weight-bold"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Purchase Details -->
                    <div class="bg-light p-3 rounded mb-4 border">
                        <h6 class="font-weight-bold text-dark mb-3 border-bottom pb-2" style="font-size:13px;">
                            <i class="fa fa-info-circle text-primary mr-1"></i> Sale & Customer Details
                        </h6>
                        <div class="row">
                            <div class="col-sm-6 col-md-3 mb-2">
                                <small class="text-muted d-block">Invoice Reference</small>
                                <span id="resSaleRef" class="font-weight-bold text-dark"></span>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2">
                                <small class="text-muted d-block">Sale Date</small>
                                <span id="resSaleDate" class="font-weight-bold text-dark"></span>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2">
                                <small class="text-muted d-block">Selling Branch</small>
                                <span id="resBranch" class="font-weight-bold text-dark"></span>
                            </div>
                            <div class="col-sm-6 col-md-3 mb-2">
                                <small class="text-muted d-block">Customer (Masked PII)</small>
                                <span id="resCustomer" class="font-weight-bold text-dark"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Action Buttons -->
                    <div class="d-flex justify-content-end flex-wrap">
                        <a id="btnProcessReturn" href="#" class="btn btn-outline-danger mr-2 mb-2 px-3 font-weight-bold">
                            <i class="fa fa-undo mr-1"></i> Process Sale Return
                        </a>
                        <a id="btnProcessExchange" href="#" class="btn btn-outline-primary mr-2 mb-2 px-3 font-weight-bold">
                            <i class="fa fa-exchange mr-1"></i> Device Exchange
                        </a>
                        <a id="btnOpenServiceClaim" href="#" class="btn btn-warning mb-2 px-3 font-weight-bold text-dark">
                            <i class="fa fa-wrench mr-1"></i> Open Service Claim
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#warrantyLookupForm').on('submit', function(e) {
            e.preventDefault();
            var q = $('#serialSearchInput').val().trim();
            if (!q) {
                showAlert('warning', 'Please enter a serial number or invoice reference.');
                return;
            }

            $('#btnSearch').prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Checking...');
            $('#alertBox').addClass('d-none');
            $('#resultCard').addClass('d-none');

            $.ajax({
                url: '{{ url("/warranty/lookup") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    query: q
                },
                success: function(data) {
                    $('#btnSearch').prop('disabled', false).html('<i class="fa fa-search mr-1"></i> Verify Policy');
                    if (data && data.success) {
                        populateResult(data);
                    } else {
                        showAlert('danger', 'Unable to retrieve warranty information.');
                    }
                },
                error: function(xhr) {
                    $('#btnSearch').prop('disabled', false).html('<i class="fa fa-search mr-1"></i> Verify Policy');
                    var msg = 'No record found.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    }
                    showAlert('danger', msg);
                }
            });
        });

        function populateResult(d) {
            $('#resSerialBadge').text('S/N: ' + d.serial_number);
            $('#resProductName').text(d.product_name + ' (' + d.product_code + ')');
            $('#resSpecsLine').html('<i class="fa fa-laptop text-info mr-1"></i> ' + d.specs_line);
            $('#resConditionBadge').text(d.product_condition);

            // Guarantee
            $('#resGuaranteeDuration').text(d.guarantee.duration);
            $('#resGuaranteeExpire').text(d.guarantee.expire_date);
            if (d.guarantee.status === 'Active') {
                $('#resGuaranteeStatusBadge').removeClass('badge-danger badge-secondary').addClass('badge-success').text('ACTIVE');
                $('#resGuaranteeRemaining').html('<span class="text-success"><i class="fa fa-clock-o"></i> ' + d.guarantee.remaining_days + ' days remaining for replacement</span>');
            } else if (d.guarantee.status === 'Expired') {
                $('#resGuaranteeStatusBadge').removeClass('badge-success badge-secondary').addClass('badge-danger').text('EXPIRED');
                $('#resGuaranteeRemaining').html('<span class="text-danger"><i class="fa fa-times-circle"></i> Guarantee replacement window expired</span>');
            } else {
                $('#resGuaranteeStatusBadge').removeClass('badge-success badge-danger').addClass('badge-secondary').text('N/A');
                $('#resGuaranteeRemaining').text('No guarantee policy registered');
            }

            // Warranty
            $('#resWarrantyDuration').text(d.warranty.duration);
            $('#resWarrantyExpire').text(d.warranty.expire_date);
            if (d.warranty.status === 'Active') {
                $('#resWarrantyStatusBadge').removeClass('badge-danger badge-secondary').addClass('badge-success').text('ACTIVE');
                $('#resWarrantyRemaining').html('<span class="text-success"><i class="fa fa-clock-o"></i> ' + d.warranty.remaining_days + ' days remaining for service</span>');
            } else if (d.warranty.status === 'Expired') {
                $('#resWarrantyStatusBadge').removeClass('badge-success badge-secondary').addClass('badge-danger').text('EXPIRED');
                $('#resWarrantyRemaining').html('<span class="text-danger"><i class="fa fa-times-circle"></i> Service warranty period expired</span>');
            } else {
                $('#resWarrantyStatusBadge').removeClass('badge-success badge-danger').addClass('badge-secondary').text('N/A');
                $('#resWarrantyRemaining').text('No warranty policy registered');
            }

            // Customer & Sale
            $('#resSaleRef').text(d.sale_reference);
            $('#resSaleDate').text(d.sale_date);
            $('#resBranch').text(d.branch);
            $('#resCustomer').text(d.customer_name + (d.customer_phone ? ' (' + d.customer_phone + ')' : ''));

            // Action Links
            $('#btnProcessReturn').attr('href', d.return_url);
            $('#btnProcessExchange').attr('href', d.exchange_url);
            $('#btnOpenServiceClaim').attr('href', '{{ url("service_jobs/create") }}?serial_number=' + encodeURIComponent(d.serial_number));

            $('#resultCard').removeClass('d-none');
        }

        function showAlert(type, msg) {
            $('#alertBox').removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert alert-' + type)
                .html('<i class="fa fa-exclamation-circle mr-2"></i> ' + msg);
        }
    });
</script>
@endpush
