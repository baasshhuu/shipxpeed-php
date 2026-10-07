@extends('layouts.app')

@section('content')
<div class="card mb-3" style="margin-top: 39px;">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Seller List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">    
                    {{-- @if (Helper::userCan(104, 'can_add'))
                    <a href="{{ route('cms.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Cms
                    </a> 
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-padding">
        <!-- 
          Wrap table in a responsive scroll container only for mobile view (<= 767px).
          This is handled via CSS: by default, no overflow (no scroll in desktop/laptop view), 
          but adds horizontal scroll on mobile using a helper class.
        -->
        <div class="sellerlist-table-responsive">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable"
                id="seller-list-table"
                style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th></th> <!-- Arrow for details toggle -->
                        <th>Agreement</th>
                        <th>User Type</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th width="100px">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <style>
            /* Table should NOT scroll on laptop/desktop,
               but WILL scroll horizontally on mobile view. */
            .sellerlist-table-responsive {
                width: 100%;
                overflow-x: unset;
                -webkit-overflow-scrolling: touch;
            }
            @media (max-width: 767.98px) {
                .sellerlist-table-responsive {
                    width: 100%;
                    overflow-x: auto;
                    /* No forced min-width, but allow scrolling if needed on mobile */
                }
                /* Optionally make table wider than container to force horizontal scroll */
                #seller-list-table {
                    min-width: 600px;
                }
            }

            .details-row {
                display: none;
                background: #f8fbfd;
            }
            .details-content {
                padding: 1.1em 1.5em;
                font-size: 1em;
            }
            .toggle-arrow {
                cursor: pointer;
                transition: transform 0.19s;
                vertical-align: middle;
                font-size: 1.04em;
            }
            .toggle-arrow.open {
                transform: rotate(90deg);
            }
            /* Responsive for detail section: stack blocks in one row on desktop, wrap on mobile */
            .seller-details-flexbox {
                display: flex;
                flex-wrap: wrap;
                gap: 28px 22px;
                align-items: stretch;
            }
            .seller-detail-block {
                min-width: 130px;
                max-width: 230px;
                flex: 1 1 150px;
                margin-bottom: 2px;
                background: #f9fdfe;
                border-radius: 7px;
                padding: .9em 1em .75em 1em;
                box-shadow: 0 1px 4px 0 rgba(5,64,112,0.03);
            }
            .seller-detail-block strong {
                display: block;
                font-size: .99em;
                color: #1473be;
                margin-bottom: 3px;
            }
            @media (max-width: 1050px) {
                .seller-detail-block {
                    min-width: 120px;
                    max-width: 100%;
                    padding-left: .7em;
                    padding-right: .7em;
                }
                .seller-details-flexbox {
                    gap: 20px 7px;
                }
            }
            @media (max-width: 767.98px) {
                .details-content {
                    padding: .7em .75em;
                    font-size: .97em;
                }
                .seller-details-flexbox {
                    flex-direction: column;
                    gap: 10px;
                }
                .seller-detail-block {
                    width: 100%;
                    min-width: 0;
                    max-width: none;
                }
            }
            .action-ellipsis-btn {
                background: none;
                border: none;
                color: #222;
                font-size: 1.19em;
                cursor: pointer;
                padding: 3px 5px;
            }
            .action-ellipsis-btn:focus {
                outline: none;
                box-shadow: 0 0 0 2px #1473be44;
            }
            /* Custom popover styling for dropdown menu to ensure visibility */
            .action-popover-menu {
                position: absolute;
                min-width: 170px;
                max-width: 275px;
                z-index: 2999;
                background: #fff;
                box-shadow: 0 8px 32px 3px rgba(60,85,130,0.26), 0 1.5px 2px 0 rgba(5,64,112,0.13);
                border-radius: 8px;
                padding: 0;
                margin: 0;
                border: 1px solid #eaecef;
                animation: fadeInPopover .18s;
            }
            @keyframes fadeInPopover {
                from {opacity: 0; transform: translateY(10px);}
                to {opacity: 1; transform: translateY(0);}
            }
            .action-popover-menu .dropdown-menu {
                display: block;
                position: static;
                float: none;
                min-width: 100%;
                background: none;
                border: none;
                box-shadow: none;
                margin: 0;
            }
        </style>
    </div>
</div>
<!-- Global Agreement Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seller Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="agreementContent">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
               <a href="{{ route('seller.agreement.download') }}" class="btn btn-primary">
                    Download Agreement
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
<script>
$(function () {
    // Table, keep only main visible columns, details in expandable
    let table = $('#seller-list-table').DataTable({
        ajax: "{{ route('seller-list') }}",
        order: [[7, 'desc']],
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: "text-center",
                width: "26px",
                render: function() {
                    return `<span class="toggle-arrow" title="Show Details" style="display:inline-block;"><i class="fa fa-chevron-right"></i></span>`;
                }
            },
            { data: 'view_agreement', orderable: false, searchable: false },
            { data: 'user_type' },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone_number' },
            { data: 'status' },
            { data: 'created_at' },
            {
                // Action column replaced with ellipsis icon; when clicked, directly show dropdown menu
                data: 'action',
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data, type, row, meta) {
                    let safeData =
                        typeof data === "string"
                            ? data.replace(/`/g, "&#96;")
                            : "";
                    return (
                        `<button type="button" class="action-ellipsis-btn" tabindex="0" title="Actions" data-action-popover-id="action-popover-${meta.row}">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <div class="d-none action-popover-content" id="action-popover-${meta.row}">
                            ${safeData}
                        </div>`
                    );
                },
            }
        ],
        responsive: false, // Responsive disabled, table will not get horizontal scroll at desktop
        autoWidth: false,  // Ensures columns don't force a min width
        rowCallback: function(row, data, index){
            // Remove details row if it exists for a redraw
            $(row).next('.details-row').remove();
        },
        drawCallback: function(settings) {
            addToggleEvents();
            bindActionEllipsisEvents();
        }
    });

    // Helper to get visible area for popovers
    function getPopoverPosition($btn, $popover) {
        // Default: below the button, left-aligned
        let offset = $btn.offset();
        let btnWidth = $btn.outerWidth();
        let btnHeight = $btn.outerHeight();
        let popoverWidth = $popover.outerWidth();
        let popoverHeight = $popover.outerHeight();
        let scrollTop = $(window).scrollTop();
        let scrollLeft = $(window).scrollLeft();
        let windowWidth = $(window).width();
        let windowHeight = $(window).height();

        let top = offset.top + btnHeight + 4;
        let left = offset.left;

        // If will overflow right, shift to left edge accordingly
        if (left + popoverWidth > windowWidth + scrollLeft - 10) {
            left = windowWidth + scrollLeft - popoverWidth - 10;
        }
        // If will overflow left
        if (left < 10 + scrollLeft) {
            left = 10 + scrollLeft;
        }
        // If will overflow bottom, open up instead
        if (top + popoverHeight > windowHeight + scrollTop - 10) {
            top = offset.top - popoverHeight - 6;
            if(top < 10 + scrollTop) top = 10 + scrollTop;
        }
        return { top, left };
    }

    // Creates a single, responsive row for the details (Pan, Aadhaar, GST, Cheque, etc.), all as blocks in a row or stacked on mobile
    function buildDetailsRow(data) {
        function renderField(field) {
            if (!field) return '<span class="text-muted">Not Provided</span>';
            if (typeof field === 'string' && field.match(/\.(jpg|jpeg|png|webp|svg)(\?|$)/i)) {
                return `<img src="${field}" alt="Uploaded Doc" style="max-height:44px; border-radius:5px;">`;
            }
            if (typeof field === 'string' && (field.startsWith('http://') || field.startsWith('https://'))) {
                return `<a href="${field}" target="_blank">${field}</a>`;
            }
            return field;
        }
        return `
            <tr class="details-row">
                <td colspan="9" class="details-content">
                    <div class="seller-details-flexbox">
                        <div class="seller-detail-block">
                            <strong>PAN Card</strong>
                            <div>${renderField(data.pan_card)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>Adhar Card Front</strong>
                            <div>${renderField(data.adhar_card_front)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>Adhar Card Back</strong>
                            <div>${renderField(data.adhar_card_back)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>Profile</strong>
                            <div>${renderField(data.profile)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>GST No</strong>
                            <div>${renderField(data.gst_no)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>GST Photo</strong>
                            <div>${renderField(data.gst_photo)}</div>
                        </div>
                        <div class="seller-detail-block">
                            <strong>Cancel Cheque</strong>
                            <div>${renderField(data.cancel_cheque)}</div>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    // Toggles responsive detail row for each seller
    function addToggleEvents() {
        $('#seller-list-table tbody').off('click', '.toggle-arrow').on('click', '.toggle-arrow', function () {
            let $arrow = $(this);
            let $row = $arrow.closest('tr');
            let rowIdx = table.row($row).index();
            let rowData = table.row($row).data();
            let $nextTr = $row.next('.details-row');

            if ($arrow.hasClass('open')) {
                $arrow.removeClass('open');
                $arrow.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $nextTr.slideUp(175, function() { $(this).remove(); });
            } else {
                // Close any other open details
                $('#seller-list-table .toggle-arrow.open').each(function(){
                    let $otherArrow = $(this);
                    $otherArrow.removeClass('open');
                    $otherArrow.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    let $maybeDetails = $otherArrow.closest('tr').next('.details-row');
                    $maybeDetails.slideUp(160, function(){ $(this).remove(); });
                });

                // Show new details row
                let $detailsRow = $(buildDetailsRow(rowData)).hide();
                $row.after($detailsRow);
                $arrow.addClass('open');
                $arrow.find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $detailsRow.slideDown(193);
            }
        });
    }

    function closeAllActionPopovers() {
        $('.action-popover-menu').remove();
        $('.action-ellipsis-btn').removeClass('active');
    }

    function bindActionEllipsisEvents() {
        // Remove previous handlers
        $('#seller-list-table tbody').off('click', '.action-ellipsis-btn');

        $('#seller-list-table tbody').on('click', '.action-ellipsis-btn', function (e) {
            e.stopPropagation();
            e.preventDefault();
            closeAllActionPopovers();
            let $btn = $(this);
            let popoverId = $btn.data('action-popover-id');
            let $content = $('#' + popoverId);
            if (!$content.length) return;

            let html = $content.html();
            // Try to find dropdown menu in the HTML (Laravel datatables + Bootstrap style)
            let $tempHolder = $('<div></div>').html(html);
            let $dropdownMenu = $tempHolder.find('.dropdown-menu');

            let popMenuHtml = $dropdownMenu.length ? $dropdownMenu[0].outerHTML : html;

            // Create a temporarily hidden popover to measure its size
            let $popover = $(`
                <div class="action-popover-menu" style="visibility:hidden;display:block;left:0px;top:0px;">
                    ${popMenuHtml}
                </div>
            `).appendTo('body');

            // For Bootstrap dropdown-menu, make sure it's visible
            if ($dropdownMenu.length) {
                $popover
                    .find('.dropdown-menu')
                    .removeClass('d-none')
                    .addClass('show')
                    .css({ position: 'static', float: 'none', margin: 0 });
            }

            // Compute position so menu is always visible (no clipping/collapsing in corners)
            let pos = getPopoverPosition($btn, $popover);
            $popover.css({
                left: pos.left + 'px',
                top: pos.top + 'px',
                visibility: 'visible'
            });

            $btn.addClass('active');

            // Ensure a click inside the popover does not close it
            $popover.on('click', function (e) {
                e.stopPropagation();
            });

            // Close popover on click outside or ESC key
            $(document).off('mousedown.actionPopover').on('mousedown.actionPopover', function(ev) {
                if (!$(ev.target).closest('.action-popover-menu, .action-ellipsis-btn').length) {
                    closeAllActionPopovers();
                }
            });
            $(document).off('keydown.actionPopover').on('keydown.actionPopover', function(ev) {
                if (ev.key === "Escape") closeAllActionPopovers();
            });
        });

        // When table is redrawn, remove existing popovers
        $('#seller-list-table').on('draw.dt', function() {
            closeAllActionPopovers();
        });
    }

    table.on('draw', function () {
        addToggleEvents();
        bindActionEllipsisEvents();
    });

    // Modal agreement
    $(document).on('click', '.view-agreement', function () {
        const id = $(this).data('id');
        $.ajax({
            url: "{{ route('seller.agreement.fetch') }}",
            data: { seller_id: id },
            success: function (res) {
                $('#agreementContent').html(`
                    <p><strong>Client Name:</strong> ${res.client_name}</p>
                    <p><strong>Client Address:</strong> ${res.client_address}</p>
                    <p><strong>Client PAN:</strong> ${res.client_pan}</p>
                    <hr><p>${res.agreement_content}</p>
                `);
                $('#agreementModal').modal('show');
            },
            error: function () {
                $('#agreementContent').html('<p class="text-danger">Agreement not found.</p>');
                $('#agreementModal').modal('show');
            }
        });
    });

    // Delete handler
    $(document).on('click', '.delete', function () {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('seller-list') }}",
                    type: 'DELETE',
                    data: { id: id },
                    success: function (data) {
                        if (data.status) {
                            Swal.fire('Deleted!', data.message, 'success');
                            table.draw();
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Hide popover menu on scroll or resize
    $(window).on('scroll resize', function() {
        closeAllActionPopovers();
    });

    addToggleEvents();
    bindActionEllipsisEvents();
});
</script>

@endsection