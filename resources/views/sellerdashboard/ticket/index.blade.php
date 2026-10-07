
@extends('layouts.sellerdash')

@section('content')
    <!-- [ Main Content ] start -->
    <div class="pc-container" style="margin-left:69px;background-color:#646dff26;">
        <div class="pc-content">

            <div class="">
                <div class="bg-white rounded shadow-sm p-3">

                    <!-- Top Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <!-- Left: Filter Icon & Date Input -->
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="daterange" class="form-control date-input"
                                placeholder="Select Date Range" style="height:36px;">
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ route('seller.ticket.add') }}" class="export-btn">Add Tickets</a>
                        </div>

                    </div>


              
                    <!-- Premium Responsive Table with Overlapping Hoverable Message Tooltip (Persists on Tooltip Hover) -->
                    <style>
                        .sx-table-wrapper {
                            background: linear-gradient(135deg, #fff 80%, #f2f6ff 100%);
                            border-radius: 16px;
                            box-shadow: 0 6px 22px 0 #485cb318;
                            overflow-x: auto;
                        }
                        .sx-table {
                            min-width: 700px;
                            background: white;
                            border-radius: 11px;
                            border: none !important;
                            overflow: hidden;
                        }
                        .sx-table th, .sx-table td {
                            padding: 0.43rem 0.3rem; /* Shorten header/cell height */
                            vertical-align: middle;
                            font-size: 0.9rem;
                            text-align:center;
                            border: none;
                            color: #151515; /* Black font */
                        }
                        .sx-table th {
                            background: linear-gradient(90deg, #eaf1ff 0, #f4fbff 100%); /* Lighter shade */
                            color: #151515; /* Black font */
                            font-weight: 600;
                            border: none !important;
                            letter-spacing: .2px;
                            font-size: 0.93rem;
                            box-shadow: none;
                        }
                        .sx-table tbody tr {
                            background: #f7fafd;
                            transition: background 0.18s;
                        }
                        .sx-table tbody tr:hover {
                            background: #edeffd;
                        }
                        .sx-badge {
                            display: inline-block;
                            font-size: 0.96em;
                            border-radius: 1.55em;
                            padding: 0.37em 1em;
                            font-weight: 600;
                            min-width: 90px;
                        }
                        .sx-badge.status-pending {
                            background: #f8fbe8;
                            color: #151515;
                            box-shadow: 0 0px 8px #ffd80024;
                        }
                        .sx-badge.status-rejected {
                            background: #ffeaea;
                            color: #151515;
                            box-shadow: 0 1px 7px #f8bdbd55;
                        }
                        .sx-badge.status-resolved {
                            background: #eafff2;
                            color: #151515;
                            box-shadow: 0 1px 7px #a7ffe244;
                        }
                        .sx-message-cell {
                            position: relative;
                            max-width: 450px;
                            min-width: 200px;
                            min-height: 50px;
                            line-height: 1.5;
                            word-break: break-word;
                            overflow: visible;
                            z-index: 10;
                        }
                        .sx-message-preview {
                            cursor: pointer;
                            background: #f6faff;
                            border-radius: 8px;
                            padding: 10px 14px;
                            max-width: 98%;
                            display: block;
                            color: #151515; /* Black font */
                            box-shadow: 0 1px 4px #bae3ff15;
                            white-space: pre-line;
                            min-height: 40px;
                            max-height: 52px;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            font-size: 1em;
                            transition: box-shadow 0.14s;
                        }
                        .sx-message-cell:hover .sx-message-preview,
                        .sx-message-preview:focus {
                            background: #e8f4ff;
                        }
                        .sx-msg-tooltip {
                            display: none;
                            position: fixed;
                            left: 0;
                            top: 0;
                            z-index: 99999;
                            min-width: 350px;
                            max-width: 500px;
                            width: max-content;
                            max-height: 220px;
                            overflow-y: auto;
                            background: #fafdff;
                            border-radius: 14px;
                            box-shadow: 0 10px 28px 0 #b8e1ff32, 0 0 0 2px #e3f0ff17;
                            padding: 15px 20px;
                            color: #151515;
                            font-size: 1.05em;
                            pointer-events: auto;
                            font-weight: 400;
                            text-align: left;
                            white-space: pre-line;
                            transition: opacity 0.17s;
                        }
                        .sx-msg-tooltip.sx-tooltip-active {
                            display: block;
                        }
                        .sx-msg-tooltip.sx-tooltip-scrollable {
                            overflow-y: auto !important;
                            scrollbar-color: #94b5f7 #f6faff;
                            scrollbar-width: thin;
                        }
                        .sx-msg-tooltip.sx-tooltip-scrollable::-webkit-scrollbar {
                            width: 8px;
                        }
                        .sx-msg-tooltip.sx-tooltip-scrollable::-webkit-scrollbar-thumb {
                            background: #a2c6ff;
                            border-radius: 6px;
                        }
                        .sx-msg-tooltip.sx-tooltip-scrollable::-webkit-scrollbar-track {
                            background: #f6faff;
                        }
                        .sx-msg-tooltip::before {
                            content: "";
                            position: absolute;
                            top: -13px;
                            left: 30px;
                            border-width: 0 15px 13px 15px;
                            border-style: solid;
                            border-color: transparent transparent #fafdff transparent;
                            filter: drop-shadow(0 -2px 4px #bddcff22);
                            z-index: 100001;
                        }
                        .sx-msg-more-indicator {
                            color: #aac6ff;
                            margin-left:10px;
                            font-size: 1.18em;
                            vertical-align: middle;
                        }
                        @media (max-width: 1170px) {
                            .sx-message-cell { max-width: 220px; min-width:110px;}
                            .sx-msg-tooltip { min-width: 175px; max-width:280px; padding: 10px 10px;}
                        }
                        @media (max-width: 770px) {
                            .sx-table { min-width: 440px;}
                            .sx-message-cell { max-width: 110px; min-width: 61px;}
                            .sx-msg-tooltip { min-width: 110px; left: 0; font-size: 0.89em; }
                        }
                        /* For mobile: font-size smaller, keep standard but consistent */
                        @media (max-width: 520px) {
                            .sx-table th, .sx-table td {
                                font-size: 0.77em !important;
                                padding: 0.17em 0.04em !important;
                            }
                            .sx-message-preview, .sx-msg-tooltip {
                                font-size: .92em !important;
                                padding: 7px 7px;
                            }
                        }
                    </style>
                    <div class="sx-table-wrapper py-2 px-1 mb-2">
                        <div class="table-responsive">
                            <table class="table sx-table mb-0">
                                <thead>
                                    <tr>
                                        <th>AWB</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                        <tr>
                                            <td>
                                                <span style="color:#151515;">
                                                    {{ $ticket->order_id }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $msg = trim($ticket->message);
                                                    $msgLimit = 85;
                                                    $needsTooltip = mb_strlen($msg) > $msgLimit;
                                                    $msgPreview = \Illuminate\Support\Str::limit($msg, $msgLimit, '...');
                                                    $tooltipId = 'msgtooltip-' . $loop->index;
                                                    $rownum = $loop->iteration;
                                                    // For really big messages, force a scrollbar in tooltip on hover
                                                    $scrollTooltip = mb_strlen($msg) > 330;
                                                @endphp
                                                <div class="sx-message-cell"
                                                    tabindex="0"
                                                    @if($needsTooltip)
                                                        onmouseenter="showSxTooltipGlobal(this, '{{ $tooltipId }}', {{ $scrollTooltip ? 'true' : 'false' }})"
                                                        onmouseleave="queueHideSxTooltipGlobal(this, '{{ $tooltipId }}')"
                                                    @endif
                                                >
                                                    <span class="sx-message-preview"
                                                        @if($needsTooltip) aria-haspopup="true" aria-expanded="false" @endif>
                                                        {!! nl2br(e($needsTooltip ? $msgPreview : $msg)) !!}
                                                        @if($needsTooltip)
                                                            <span class="sx-msg-more-indicator" title="Hover to see full message">
                                                                <i class="bi bi-eye"></i>
                                                            </span>
                                                        @endif
                                                    </span>
                                                </div>
                                                @if($needsTooltip)
                                                    <span class="sx-msg-tooltip @if($scrollTooltip) sx-tooltip-scrollable @endif"
                                                        id="{{ $tooltipId }}"
                                                        onmouseenter="keepSxTooltipOpenGlobal(this)"
                                                        onmouseleave="hideSxTooltipGlobalByTooltip(this)"
                                                        style="display:none;"
                                                    >{!! nl2br(e($msg)) !!}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($ticket->status === 'Pending')
                                                    <span class="sx-badge status-pending">Pending</span>
                                                @elseif($ticket->status === 'Rejected')
                                                    <span class="sx-badge status-rejected">Rejected</span>
                                                @elseif($ticket->status === 'Resolved')
                                                    <span class="sx-badge status-resolved">Resolved</span>
                                                @else
                                                    <span class="sx-badge" style="background:#f4f4fb;color:#151515;">{{ $ticket->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span style="font-weight:500;color:#151515;">
                                                    {{ $ticket->created_at->format('Y-m-d') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span style="color:#151515;">
                                                    {{ $ticket->updated_at->format('Y-m-d') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" style="text-align:center;color:#b3b3cd;">
                                                No tickets found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Bootstrap Icons for tooltip icon if not already loaded -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
                    <script>
                        let sxTooltipTimeout = null;
                        let sxActiveTooltip = null;
                        function showSxTooltipGlobal(cell, tooltipId, forceScroll) {
                            if (sxTooltipTimeout) clearTimeout(sxTooltipTimeout);
                            // Hide previous if any
                            if (sxActiveTooltip && sxActiveTooltip.id !== tooltipId) {
                                sxActiveTooltip.classList.remove('sx-tooltip-active');
                                sxActiveTooltip.style.display = 'none';
                            }
                            let tooltip = document.getElementById(tooltipId);
                            if (!tooltip) return;
                            // Add scrollable class if forceScroll param
                            if (forceScroll) {
                                tooltip.classList.add('sx-tooltip-scrollable');
                            } else {
                                tooltip.classList.remove('sx-tooltip-scrollable');
                            }
                            // Calculate position - make tooltip overlap, near cursor cell 
                            const rect = cell.getBoundingClientRect();
                            // Default: try below, else above (stay in viewport)
                            let left = Math.max(rect.left - 12, 16);
                            if (left + tooltip.offsetWidth > window.innerWidth)
                                left = Math.max(window.innerWidth - tooltip.offsetWidth - 16, 8);
                            let top = rect.bottom + 8;
                            if (top + tooltip.offsetHeight > window.innerHeight) {
                                top = rect.top - tooltip.offsetHeight - 13;
                                if(top < 12) top = 12;
                                tooltip.style.setProperty('--arrow-up-top', 'auto');
                            }
                            tooltip.style.left = `${left}px`;
                            tooltip.style.top = `${top}px`;
                            tooltip.classList.add('sx-tooltip-active');
                            tooltip.style.display = 'block';
                            sxActiveTooltip = tooltip;
                        }
                        function queueHideSxTooltipGlobal(cell, tooltipId) {
                            sxTooltipTimeout = setTimeout(function() {
                                let tooltip = document.getElementById(tooltipId);
                                // Only hide if not hovered
                                if (tooltip && !tooltip.matches(':hover')) {
                                    tooltip.classList.remove('sx-tooltip-active');
                                    tooltip.style.display = 'none';
                                    sxActiveTooltip = null;
                                }
                            }, 140);
                        }
                        function keepSxTooltipOpenGlobal(tooltip) {
                            if (sxTooltipTimeout) clearTimeout(sxTooltipTimeout);
                            tooltip.classList.add('sx-tooltip-active');
                            tooltip.style.display = 'block';
                            sxActiveTooltip = tooltip;
                        }
                        function hideSxTooltipGlobalByTooltip(tooltip) {
                            sxTooltipTimeout = setTimeout(function() {
                                tooltip.classList.remove('sx-tooltip-active');
                                tooltip.style.display = 'none';
                                sxActiveTooltip = null;
                            }, 140);
                        }
                        window.addEventListener('scroll', function() {
                            if (sxActiveTooltip) {
                                sxActiveTooltip.classList.remove('sx-tooltip-active');
                                sxActiveTooltip.style.display = 'none';
                                sxActiveTooltip = null;
                            }
                        }, true);
                        window.addEventListener('resize', function() {
                            if (sxActiveTooltip) {
                                sxActiveTooltip.classList.remove('sx-tooltip-active');
                                sxActiveTooltip.style.display = 'none';
                                sxActiveTooltip = null;
                            }
                        });
                    </script>


                    <!-- Pagination -->
                    <nav class="d-flex justify-content-end mt-3">
                      
                    </nav>
                </div>
            </div>

        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection
