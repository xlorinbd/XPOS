@extends('backend.layout.main')

@push('css')
<style>
    .quick-paste-card {
        margin-bottom: 20px;
    }
    .quick-paste-header {
        background: #7c5cc4;
        color: #ffffff;
        padding: 12px 20px;
    }
    .paste-box {
        border: 2px dashed #94a3b8;
        background-color: #f8fafc;
        border-radius: 4px;
        padding: 12px;
        min-height: 90px;
        font-family: monospace;
        font-size: 13px;
        transition: all 0.2s ease;
    }
    .paste-box:focus {
        border-color: #7c5cc4;
        background-color: #ffffff;
        outline: none;
    }
    .table-preview-container {
        max-height: 550px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
    }
    .table-preview {
        font-size: 12px;
        white-space: nowrap;
        margin-bottom: 0;
    }
    .table-preview th {
        background-color: #7c5cc4;
        color: #ffffff;
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 8px 10px;
        font-weight: 600;
        border: none;
    }
    .table-preview td {
        padding: 4px 6px;
        vertical-align: middle;
        background: #fff;
    }
    .table-preview input, .table-preview select, .table-preview textarea {
        font-size: 12px;
        padding: 3px 6px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        width: 100%;
        min-width: 90px;
    }
    .table-preview input:focus, .table-preview select:focus, .table-preview textarea:focus {
        border-color: #3b82f6;
        outline: none;
    }
    .row-invalid td {
        background-color: #fff1f2 !important;
    }
    .cell-error {
        border: 2px solid #ef4444 !important;
        background-color: #fee2e2 !important;
    }
    .error-tag {
        color: #ef4444;
        font-size: 11px;
        font-weight: 600;
        display: block;
        margin-top: 2px;
    }
    .badge-norm {
        background: #dbeafe;
        color: #1e40af;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        max-width: 170px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .badge-dup {
        background: #fee2e2;
        color: #dc2626;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<section class="forms">
    <div class="container-fluid">
        <!-- Top Title Bar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0 text-dark font-weight-bold"> Excel / Google Sheets Clipboard Quick Paste</h3>
                <small class="text-muted">Batch import gadget specs & serials directly from clipboard table with local AI processor normalization.</small>
            </div>
            <div>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="dripicons-arrow-left"></i> Back to Product List
                </a>
            </div>
        </div>

        <!-- 1. Defaults & Target Config Panel -->
        <div class="card quick-paste-card">
            <div class="card-header bg-light py-2">
                <strong class="text-dark"><i class="dripicons-gear"></i> Default Import Parameters</strong>
                <span class="text-muted small ml-2">(Applied to any rows missing warehouse, unit, or category)</span>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-3">
                        <label class="font-weight-bold text-dark mb-1">Target Warehouse <span class="text-danger">*</span></label>
                        <select id="default_warehouse_id" class="form-control form-control-sm">
                            @foreach($lims_warehouse_list as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold text-dark mb-1">Default Category</label>
                        <select id="default_category_id" class="form-control form-control-sm">
                            @foreach($lims_category_list as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="font-weight-bold text-dark mb-1">Default Unit</label>
                        <select id="default_unit_id" class="form-control form-control-sm">
                            @foreach($lims_unit_list as $u)
                                <option value="{{ $u->id }}">{{ $u->unit_name }} ({{ $u->unit_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="btn-copy-template" class="btn btn-info btn-sm btn-block">
                            <i class="dripicons-copy"></i> Copy Sample TSV Format
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Clipboard Paste Area -->
        <div class="card quick-paste-card">
            <div class="quick-paste-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold">
                    <i class="dripicons-clipboard"></i> Paste Data from Excel / Google Sheets
                </span>
                <span class="badge badge-light font-weight-bold" id="row-count-badge">0 Rows Loaded</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    <strong>Instructions:</strong> Copy rows from Excel or Google Sheets (including or excluding headers) and paste (Ctrl+V) inside the box below. It will instantly parse all columns into the editable grid.
                </p>
                <textarea id="paste_box" class="form-control paste-box" rows="3" placeholder="Click here and press Ctrl+V to paste table data from Excel / Sheets..."></textarea>
                
                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <span class="text-info font-weight-bold">Expected Column Order:</span> 
                        Name | Brand | Category | Model | Processor | RAM | Storage | Display | Graphics | Adapter | Condition | Cost | Price | Discount Price | Last Border Price | Serials
                    </div>
                    <div>
                        <button type="button" id="btn-clear" class="btn btn-outline-danger btn-sm mr-2" style="display: none;">
                            <i class="dripicons-trash"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Live Data Preview & Edit Grid -->
        <div class="card quick-paste-card" id="preview-card" style="display: none;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <div>
                    <strong class="text-dark"><i class="dripicons-preview"></i> Interactive Data Preview</strong>
                    <span class="text-muted small ml-2">(You can edit any cell directly before saving)</span>
                </div>
                <div>
                    <span id="validation-summary" class="mr-3 small font-weight-bold"></span>
                    <button type="button" id="btn-save-products" class="btn btn-success font-weight-bold shadow-sm px-4">
                        <i class="dripicons-checkmark"></i> Save Valid Products (<span id="valid-btn-count">0</span>)
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-preview-container">
                    <table class="table table-bordered table-hover table-preview" id="preview-table">
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th style="width: 40px;">Act</th>
                                <th style="min-width: 170px;">Product Name *</th>
                                <th style="min-width: 100px;">Brand</th>
                                <th style="min-width: 110px;">Category</th>
                                <th style="min-width: 100px;">Model</th>
                                <th style="min-width: 160px;">Processor</th>
                                <th style="min-width: 85px;">RAM</th>
                                <th style="min-width: 85px;">Storage</th>
                                <th style="min-width: 90px;">Display</th>
                                <th style="min-width: 100px;">Graphics</th>
                                <th style="min-width: 105px;">Condition</th>
                                <th style="min-width: 90px;">Adapter</th>
                                <th style="min-width: 90px;">Cost (৳)</th>
                                <th style="min-width: 100px;">Regular Price * (৳)</th>
                                <th style="min-width: 100px;">Discount Price (৳)</th>
                                <th style="min-width: 100px;">Border Price (৳)</th>
                                <th style="min-width: 160px;">Serials (SN or SN:condition)</th>
                            </tr>
                        </thead>
                        <tbody id="preview-tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="dripicons-information"></i> 
                    <strong>Business Rule:</strong> Last Border Price ≤ Discount Price ≤ Regular Price. Values with currency signs (৳, Tk) or commas are cleaned automatically.
                </small>
                <button type="button" id="btn-save-products-bottom" class="btn btn-success btn-sm font-weight-bold px-3">
                    <i class="dripicons-checkmark"></i> Save Valid Products
                </button>
            </div>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    // Processor local normalizer (mirroring backend ProcessorNormalizer)
    function normalizeProcessor(raw) {
        if (!raw || !raw.trim()) return { normalized: '', is_recognized: false };
        let clean = raw.trim();
        let m;

        // Apple Silicon
        if ((m = clean.match(/^m([1-4])\s*(ultra|max|pro)?/i))) {
            let gen = 'M' + m[1];
            let tier = m[2] ? ' ' + m[2].charAt(0).toUpperCase() + m[2].slice(1).toLowerCase() : '';
            return { normalized: `Apple ${gen}${tier} Chip`, is_recognized: true };
        }
        // Intel Core Ultra
        if ((m = clean.match(/(?:intel\s*)?(?:core\s*)?ultra\s*([579])\s*[-]?\s*(\d{3}[a-z]*)/i))) {
            return { normalized: `Intel Core Ultra ${m[1]} ${m[2].toUpperCase()}`, is_recognized: true };
        }
        // Intel Core i3/i5/i7/i9
        if ((m = clean.match(/(?:intel\s*)?(?:core\s*)?i([3579])\s*(?:[-]?\s*(?:\d+th\s*gen)?\s*)?[-]?\s*(\d{4,5}[a-z]*)/i))) {
            return { normalized: `Intel Core i${m[1]}-${m[2].toUpperCase()}`, is_recognized: true };
        }
        // AMD Ryzen
        if ((m = clean.match(/(?:amd\s*)?(?:r|ryzen)\s*([3579])\s*(?:ai)?\s*[-]?\s*(\d{4}[a-z]*)/i))) {
            return { normalized: `AMD Ryzen ${m[1]} ${m[2].toUpperCase()}`, is_recognized: true };
        }
        // AMD Athlon
        if ((m = clean.match(/(?:amd\s*)?athlon\s*(?:silver|gold)?\s*[-]?\s*([0-9a-z]+)/i))) {
            return { normalized: `AMD Athlon ${m[1].toUpperCase()}`, is_recognized: true };
        }
        // Intel Celeron / Pentium
        if ((m = clean.match(/(?:intel\s*)?(celeron|pentium)\s*[-]?\s*([0-9a-z]+)/i))) {
            let fam = m[1].charAt(0).toUpperCase() + m[1].slice(1).toLowerCase();
            return { normalized: `Intel ${fam} ${m[2].toUpperCase()}`, is_recognized: true };
        }

        return { normalized: clean, is_recognized: false };
    }

    // Number/Price cleaner (strips ৳, Tk, commas, spaces)
    function cleanPrice(val) {
        if (val === null || val === undefined || val === '') return null;
        let str = String(val).replace(/[^\d.]/g, '');
        let num = parseFloat(str);
        return isNaN(num) ? null : num;
    }

    let parsedRows = [];

    // Copy Sample TSV to Clipboard
    $('#btn-copy-template').on('click', function() {
        const sampleHeader = "Product Name\tBrand\tCategory\tModel\tProcessor\tRAM\tStorage\tDisplay\tGraphics\tAdapter\tCondition\tCost\tPrice\tDiscount Price\tLast Border Price\tSerials";
        const sampleRow1 = "HP EliteBook 840 G9\tHP\tLaptop\t840 G9\ti7 1260p\t16GB DDR5\t512GB NVMe\t14\" FHD IPS\tIntel Iris Xe\tOriginal 65W\tUsed\t৳65,000\t৳75,000\t৳72,000\t৳70,000\tHP840-001:Minor scratch on lid\nHP840-002";
        const sampleRow2 = "Apple MacBook Air M2\tApple\tLaptop\tA2681\tm2\t8GB Unified\t256GB SSD\t13.6\" Liquid Retina\t8-Core GPU\tOriginal 30W\tOpen Box\t92000\t108,000\t105,000\t102,000\tMACM2-101\nMACM2-102:Complete box";
        
        const textToCopy = sampleHeader + "\n" + sampleRow1 + "\n" + sampleRow2;
        navigator.clipboard.writeText(textToCopy).then(function() {
            alert("Sample TSV format copied to clipboard! You can paste it directly into the paste box below.");
        });
    });

    // Paste event listener
    $('#paste_box').on('paste', function(e) {
        e.preventDefault();
        let clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
        let pastedText = clipboardData.getData('text');
        if (!pastedText) return;

        parseClipboardData(pastedText);
    });

    // Parse pasted TSV text
    function parseClipboardData(text) {
        let lines = text.split(/\r\n|\n|\r/);
        if (!lines.length) return;

        let rows = [];
        let isFirst = true;

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            if (!line.trim()) continue;

            let cols = line.split('\t');

            // Skip header if matches "product name" or "name"
            if (isFirst && (cols[0].trim().toLowerCase() === 'product name' || cols[0].trim().toLowerCase() === 'name')) {
                isFirst = false;
                continue;
            }
            isFirst = false;

            // Preserve empty tabs without shifting columns
            rows.push({
                name: cols[0] ? cols[0].trim() : '',
                brand: cols[1] ? cols[1].trim() : '',
                category: cols[2] ? cols[2].trim() : '',
                model: cols[3] ? cols[3].trim() : '',
                processor: cols[4] ? cols[4].trim() : '',
                ram: cols[5] ? cols[5].trim() : '',
                storage: cols[6] ? cols[6].trim() : '',
                display: cols[7] ? cols[7].trim() : '',
                dedicated_graphics: cols[8] ? cols[8].trim() : '',
                adapter_condition: cols[9] ? cols[9].trim() : '',
                product_condition: cols[10] ? cols[10].trim() : 'Used',
                cost: cols[11] ? cols[11].trim() : '',
                price: cols[12] ? cols[12].trim() : '',
                discount_price: cols[13] ? cols[13].trim() : '',
                last_border_price: cols[14] ? cols[14].trim() : '',
                serials: cols[15] ? cols[15].trim() : '',
                error_message: null
            });
        }

        if (rows.length > 0) {
            parsedRows = rows;
            renderPreviewGrid();
            $('#paste_box').val('');
        }
    }

    // Render Preview Grid with real-time validation
    function renderPreviewGrid() {
        if (!parsedRows.length) {
            $('#preview-card').hide();
            $('#btn-clear').hide();
            $('#row-count-badge').text('0 Rows Loaded');
            return;
        }

        $('#preview-card').show();
        $('#btn-clear').show();
        $('#row-count-badge').text(parsedRows.length + ' Rows Loaded');

        let tbody = $('#preview-tbody');
        tbody.empty();

        // 1. In-batch duplicate serial check
        let serialCounts = {};
        parsedRows.forEach((r) => {
            if (r.serials) {
                let sArr = r.serials.split(/[\r\n,]+/);
                sArr.forEach(s => {
                    let sn = s.split(':')[0].trim();
                    if (sn) {
                        serialCounts[sn] = (serialCounts[sn] || 0) + 1;
                    }
                });
            }
        });

        let validCount = 0;
        let invalidCount = 0;

        parsedRows.forEach((row, idx) => {
            let rowErrors = [];

            // Name check
            if (!row.name || !row.name.trim()) {
                rowErrors.push("Product Name is required.");
            }

            // Price parsing
            let costVal = cleanPrice(row.cost);
            let priceVal = cleanPrice(row.price);
            let discountVal = cleanPrice(row.discount_price);
            let borderVal = cleanPrice(row.last_border_price);

            if (priceVal === null || priceVal <= 0) {
                rowErrors.push("Regular Price must be > 0.");
            }

            // Price hierarchy rule: last_border_price <= discount_price <= price
            if (borderVal !== null && priceVal !== null && borderVal > priceVal) {
                rowErrors.push(`Border Price (৳${borderVal}) > Regular Price (৳${priceVal}).`);
            }
            if (discountVal !== null && priceVal !== null && discountVal > priceVal) {
                rowErrors.push(`Discount Price (৳${discountVal}) > Regular Price (৳${priceVal}).`);
            }
            if (borderVal !== null && discountVal !== null && borderVal > discountVal) {
                rowErrors.push(`Border Price (৳${borderVal}) > Discount Price (৳${discountVal}).`);
            }

            // Duplicate serial check in batch
            let hasDupSerial = false;
            let dupSerialNames = [];
            if (row.serials) {
                let sArr = row.serials.split(/[\r\n,]+/);
                sArr.forEach(s => {
                    let sn = s.split(':')[0].trim();
                    if (sn && serialCounts[sn] > 1) {
                        hasDupSerial = true;
                        if (!dupSerialNames.includes(sn)) dupSerialNames.push(sn);
                    }
                });
            }
            if (hasDupSerial) {
                rowErrors.push(`Duplicate serial(s) in batch: [${dupSerialNames.join(', ')}]`);
            }

            // Server-returned error message
            if (row.error_message) {
                rowErrors.push(row.error_message);
            }

            let isValid = rowErrors.length === 0;
            if (isValid) validCount++;
            else invalidCount++;

            // Processor normalizer preview
            let procNorm = normalizeProcessor(row.processor);

            let trClass = isValid ? '' : 'row-invalid';

            let tr = $(`
                <tr data-idx="${idx}" class="${trClass}">
                    <td class="text-center font-weight-bold text-muted">${idx + 1}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-xs btn-del-row" data-idx="${idx}" title="Remove Row" style="padding: 1px 5px; font-size: 11px;">
                            <i class="dripicons-cross"></i>
                        </button>
                    </td>
                    <td>
                        <input type="text" class="cell-name ${(!row.name ? 'cell-error' : '')}" value="${escapeHtml(row.name)}" placeholder="Required product name">
                        ${rowErrors.length ? `<span class="error-tag"><i class="dripicons-warning"></i> ${rowErrors.join(' | ')}</span>` : ''}
                    </td>
                    <td><input type="text" class="cell-brand" value="${escapeHtml(row.brand)}" placeholder="Brand"></td>
                    <td><input type="text" class="cell-category" value="${escapeHtml(row.category)}" placeholder="Category"></td>
                    <td><input type="text" class="cell-model" value="${escapeHtml(row.model)}" placeholder="Model"></td>
                    <td>
                        <input type="text" class="cell-processor" value="${escapeHtml(row.processor)}" placeholder="e.g. i7 13700H">
                        ${procNorm.is_recognized ? `<span class="badge-norm mt-1" title="${procNorm.normalized}"> ${procNorm.normalized}</span>` : ''}
                    </td>
                    <td><input type="text" class="cell-ram" value="${escapeHtml(row.ram)}" placeholder="e.g. 16GB"></td>
                    <td><input type="text" class="cell-storage" value="${escapeHtml(row.storage)}" placeholder="e.g. 512GB"></td>
                    <td><input type="text" class="cell-display" value="${escapeHtml(row.display)}" placeholder="e.g. 14 FHD"></td>
                    <td><input type="text" class="cell-graphics" value="${escapeHtml(row.dedicated_graphics)}" placeholder="GPU"></td>
                    <td>
                        <select class="cell-condition">
                            <option value="used" ${(row.product_condition||'').toLowerCase().includes('used') ? 'selected' : ''}>Used</option>
                            <option value="open_box" ${(row.product_condition||'').toLowerCase().includes('open') ? 'selected' : ''}>Open Box</option>
                            <option value="brand_new" ${(row.product_condition||'').toLowerCase().includes('brand') ? 'selected' : ''}>Brand New</option>
                            <option value="box_opened" ${(row.product_condition||'').toLowerCase().includes('box') && !(row.product_condition||'').toLowerCase().includes('open') ? 'selected' : ''}>Box Opened</option>
                        </select>
                    </td>
                    <td><input type="text" class="cell-adapter" value="${escapeHtml(row.adapter_condition)}" placeholder="Adapter"></td>
                    <td><input type="text" class="cell-cost text-right" value="${escapeHtml(row.cost)}" placeholder="0.00"></td>
                    <td>
                        <input type="text" class="cell-price text-right ${priceVal === null || priceVal <= 0 ? 'cell-error' : ''}" value="${escapeHtml(row.price)}" placeholder="0.00">
                    </td>
                    <td>
                        <input type="text" class="cell-discount text-right ${discountVal && priceVal && discountVal > priceVal ? 'cell-error' : ''}" value="${escapeHtml(row.discount_price)}" placeholder="0.00">
                    </td>
                    <td>
                        <input type="text" class="cell-border text-right ${(borderVal && priceVal && borderVal > priceVal) || (borderVal && discountVal && borderVal > discountVal) ? 'cell-error' : ''}" value="${escapeHtml(row.last_border_price)}" placeholder="0.00">
                    </td>
                    <td>
                        <textarea class="cell-serials ${hasDupSerial ? 'cell-error' : ''}" rows="2" placeholder="SN1:cond\nSN2">${escapeHtml(row.serials)}</textarea>
                        ${hasDupSerial ? `<span class="badge-dup">Duplicate Serial</span>` : ''}
                    </td>
                </tr>
            `);

            tbody.append(tr);
        });

        // Summary counts
        $('#valid-btn-count').text(validCount);
        if (invalidCount > 0) {
            $('#validation-summary').html(`<span class="text-danger"><i class="dripicons-warning"></i> ${invalidCount} rows have errors. Only ${validCount} valid rows will be saved.</span>`);
        } else {
            $('#validation-summary').html(`<span class="text-success"><i class="dripicons-checkmark"></i> All ${validCount} rows are valid!</span>`);
        }
    }

    // In-cell live update handlers
    $(document).on('change keyup', '#preview-tbody input, #preview-tbody select, #preview-tbody textarea', function() {
        let tr = $(this).closest('tr');
        let idx = parseInt(tr.data('idx'));
        if (isNaN(idx) || !parsedRows[idx]) return;

        parsedRows[idx].name = tr.find('.cell-name').val();
        parsedRows[idx].brand = tr.find('.cell-brand').val();
        parsedRows[idx].category = tr.find('.cell-category').val();
        parsedRows[idx].model = tr.find('.cell-model').val();
        parsedRows[idx].processor = tr.find('.cell-processor').val();
        parsedRows[idx].ram = tr.find('.cell-ram').val();
        parsedRows[idx].storage = tr.find('.cell-storage').val();
        parsedRows[idx].display = tr.find('.cell-display').val();
        parsedRows[idx].dedicated_graphics = tr.find('.cell-graphics').val();
        parsedRows[idx].product_condition = tr.find('.cell-condition').val();
        parsedRows[idx].adapter_condition = tr.find('.cell-adapter').val();
        parsedRows[idx].cost = tr.find('.cell-cost').val();
        parsedRows[idx].price = tr.find('.cell-price').val();
        parsedRows[idx].discount_price = tr.find('.cell-discount').val();
        parsedRows[idx].last_border_price = tr.find('.cell-border').val();
        parsedRows[idx].serials = tr.find('.cell-serials').val();
        parsedRows[idx].error_message = null; // reset server error on edit

        // Live processor tag update without full re-render
        if ($(this).hasClass('cell-processor')) {
            let norm = normalizeProcessor(parsedRows[idx].processor);
            tr.find('.badge-norm').remove();
            if (norm.is_recognized) {
                tr.find('.cell-processor').after(`<span class="badge-norm mt-1" title="${norm.normalized}"> ${norm.normalized}</span>`);
            }
        }
    });

    // Delete single row
    $(document).on('click', '.btn-del-row', function() {
        let idx = parseInt($(this).data('idx'));
        if (!isNaN(idx)) {
            parsedRows.splice(idx, 1);
            renderPreviewGrid();
        }
    });

    // Clear All
    $('#btn-clear').on('click', function() {
        if (confirm("Are you sure you want to clear all rows?")) {
            parsedRows = [];
            renderPreviewGrid();
        }
    });

    // Save Valid Products (Partial Success Model)
    $('#btn-save-products, #btn-save-products-bottom').on('click', function() {
        if (!parsedRows.length) {
            alert("No rows available to save.");
            return;
        }

        let warehouseId = $('#default_warehouse_id').val();
        let defaultUnitId = $('#default_unit_id').val();
        let defaultCategoryId = $('#default_category_id').val();

        let btn = $('#btn-save-products');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: "{{ route('products.processQuickPaste') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                warehouse_id: warehouseId,
                default_unit_id: defaultUnitId,
                default_category_id: defaultCategoryId,
                rows: parsedRows
            },
            success: function(response) {
                btn.prop('disabled', false).html(originalText);

                if (response.success) {
                    let successCount = response.success_count;
                    let failedCount = response.failed_count;

                    if (failedCount === 0) {
                        // All succeeded!
                        alert(` Success! All ${successCount} products were created successfully.`);
                        window.location.href = "{{ route('products.index') }}";
                    } else {
                        // Partial success: keep failed rows in table with their error messages
                        parsedRows = response.failed_rows;
                        renderPreviewGrid();
                        alert(`<i class="fa fa-exclamation-triangle"></i> Partial Success:\n- ${successCount} products saved successfully.\n- ${failedCount} rows failed validation and remain in the table for correction.`);
                    }
                } else {
                    alert("Error: " + (response.message || "Failed to process products."));
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                let msg = xhr.responseJSON ? xhr.responseJSON.message : "Server error occurred.";
                alert("Error: " + msg);
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
@endpush
