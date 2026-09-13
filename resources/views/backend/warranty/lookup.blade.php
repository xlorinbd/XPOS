@extends('backend.layout.main')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Warranty & Guarantee Verification</h3>
            </div>
            <div class="card-body">
                <form id="warrantyLookupForm" onsubmit="return false;">
                    @csrf
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <label><strong>Scan / Enter Serial Number, IMEI or Invoice Reference *</strong></label>
                            <div class="input-group">
                                <input type="text" id="serialSearchInput" class="form-control" placeholder="Type or scan Serial (e.g. SN-...) or Invoice Reference..." autofocus autocomplete="off">
                                <div class="input-group-append">
                                    <button type="submit" id="btnSearch" class="btn btn-primary">
                                        <i class="dripicons-search"></i> Verify Policy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div id="alertBox" class="mt-3 d-none"></div>

                <!-- Verification Result Section -->
                <div id="resultCard" class="d-none mt-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 id="resProductName" class="mb-0"></h4>
                            <span id="resSerialBadge" class="badge badge-info" style="font-size:13px;"></span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <span id="resConditionBadge" class="badge badge-secondary mr-2"></span>
                                <span id="resSpecsLine" class="text-muted"></span>
                            </div>

                            <div class="row">
                                <!-- Replacement Guarantee Box -->
                                <div class="col-md-6 mb-3">
                                    <div class="card card-body bg-light border h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Replacement Guarantee</strong>
                                            <span id="resGuaranteeStatusBadge" class="badge"></span>
                                        </div>
                                        <h5 id="resGuaranteeDuration" class="mb-1"></h5>
                                        <div class="small text-muted mb-1">
                                            <strong>Expires On:</strong> <span id="resGuaranteeExpire"></span>
                                        </div>
                                        <div id="resGuaranteeRemaining" class="small font-weight-bold"></div>
                                    </div>
                                </div>

                                <!-- Service Warranty Box -->
                                <div class="col-md-6 mb-3">
                                    <div class="card card-body bg-light border h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Service Warranty</strong>
                                            <span id="resWarrantyStatusBadge" class="badge"></span>
                                        </div>
                                        <h5 id="resWarrantyDuration" class="mb-1"></h5>
                                        <div class="small text-muted mb-1">
                                            <strong>Expires On:</strong> <span id="resWarrantyExpire"></span>
                                        </div>
                                        <div id="resWarrantyRemaining" class="small font-weight-bold"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer & Purchase Details -->
                            <div class="mt-2">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th colspan="4">Sale & Customer Details</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="width:25%;"><strong>Invoice Reference:</strong></td>
                                            <td style="width:25%;" id="resSaleRef"></td>
                                            <td style="width:25%;"><strong>Sale Date:</strong></td>
                                            <td style="width:25%;" id="resSaleDate"></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Selling Branch:</strong></td>
                                            <td id="resBranch"></td>
                                            <td><strong>Customer:</strong></td>
                                            <td id="resCustomer"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Action Buttons -->
                            <div class="text-right mt-3">
                                <a id="btnProcessReturn" href="#" class="btn btn-outline-danger mr-1">
                                    <i class="dripicons-return"></i> Sale Return
                                </a>
                                <a id="btnProcessExchange" href="#" class="btn btn-outline-primary mr-1">
                                    <i class="dripicons-swap"></i> Device Exchange
                                </a>
                                <a id="btnOpenServiceClaim" href="#" class="btn btn-warning">
                                    <i class="dripicons-wrench"></i> Open Service Claim
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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

            $('#btnSearch').prop('disabled', true).text('Checking...');
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
                    $('#btnSearch').prop('disabled', false).html('<i class="dripicons-search"></i> Verify Policy');
                    if (data && data.success) {
                        populateResult(data);
                    } else {
                        showAlert('danger', 'Unable to retrieve warranty information.');
                    }
                },
                error: function(xhr) {
                    $('#btnSearch').prop('disabled', false).html('<i class="dripicons-search"></i> Verify Policy');
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
            $('#resSpecsLine').text(d.specs_line);
            $('#resConditionBadge').text(d.product_condition);

            // Guarantee
            $('#resGuaranteeDuration').text(d.guarantee.duration);
            $('#resGuaranteeExpire').text(d.guarantee.expire_date);
            if (d.guarantee.status === 'Active') {
                $('#resGuaranteeStatusBadge').removeClass('badge-danger badge-secondary').addClass('badge-success').text('ACTIVE');
                $('#resGuaranteeRemaining').html('<span class="text-success">' + d.guarantee.remaining_days + ' days remaining for replacement</span>');
            } else if (d.guarantee.status === 'Expired') {
                $('#resGuaranteeStatusBadge').removeClass('badge-success badge-secondary').addClass('badge-danger').text('EXPIRED');
                $('#resGuaranteeRemaining').html('<span class="text-danger">Guarantee replacement window expired</span>');
            } else {
                $('#resGuaranteeStatusBadge').removeClass('badge-success badge-danger').addClass('badge-secondary').text('N/A');
                $('#resGuaranteeRemaining').text('No guarantee policy registered');
            }

            // Warranty
            $('#resWarrantyDuration').text(d.warranty.duration);
            $('#resWarrantyExpire').text(d.warranty.expire_date);
            if (d.warranty.status === 'Active') {
                $('#resWarrantyStatusBadge').removeClass('badge-danger badge-secondary').addClass('badge-success').text('ACTIVE');
                $('#resWarrantyRemaining').html('<span class="text-success">' + d.warranty.remaining_days + ' days remaining for service</span>');
            } else if (d.warranty.status === 'Expired') {
                $('#resWarrantyStatusBadge').removeClass('badge-success badge-secondary').addClass('badge-danger').text('EXPIRED');
                $('#resWarrantyRemaining').html('<span class="text-danger">Service warranty period expired</span>');
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
                .text(msg);
        }
    });
</script>
@endpush
