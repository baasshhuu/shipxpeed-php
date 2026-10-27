@extends('layouts.app')

@section('css')
<style>
    .tk-wrap { --line: var(--sx-border, #2a3757); --card: var(--sx-surface-2, #18223a); --muted: var(--sx-muted, #8592ad); }
    .tk-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
    .tk-stat { border:1px solid var(--line); background:var(--card); border-radius:10px; padding:10px 16px; min-width:120px; text-decoration:none; color:inherit; }
    .tk-stat.active { border-color: var(--sx-primary, #4f8cff); }
    .tk-stat b { display:block; font-size:20px; line-height:1.2; }
    .tk-stat span { font-size:12px; color:var(--muted); }
    .tk-filter { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
    .tk-filter input { max-width:320px; }
    .tk-list { display:flex; flex-direction:column; gap:10px; }
    .tk-item { border:1px solid var(--line); background:var(--card); border-radius:10px; padding:14px 16px; }
    .tk-top { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:flex-start; }
    .tk-no { font-weight:700; font-size:14px; }
    .tk-meta { font-size:12px; color:var(--muted); margin-top:3px; }
    .tk-msg { margin:10px 0 0; font-size:14px; white-space:pre-wrap; word-break:break-word; }
    .tk-reply-old { margin-top:10px; font-size:13px; border-left:3px solid var(--sx-success, #22c55e); padding:6px 10px; background: var(--sx-success-soft, rgba(34,197,94,.08)); white-space:pre-wrap; }
    .tk-form { display:grid; grid-template-columns: 1fr 180px auto; gap:8px; margin-top:12px; align-items:start; }
    .tk-form textarea { min-height:40px; }
    @media (max-width: 720px) { .tk-form { grid-template-columns: 1fr; } }
    .tk-badge { font-size:11px; font-weight:700; letter-spacing:.03em; padding:3px 10px; border-radius:20px; white-space:nowrap; }
    .tk-Pending { background: rgba(245,165,36,.15); color:#f5a524; }
    .tk-InProgress { background: rgba(79,140,255,.15); color:#4f8cff; }
    .tk-Resolved { background: rgba(34,197,94,.15); color:#22c55e; }
    .tk-Rejected { background: rgba(240,79,95,.15); color:#f04f5f; }
    .tk-empty { border:1px dashed var(--line); border-radius:10px; padding:24px; text-align:center; color:var(--muted); }
</style>
@endsection

@section('content')
<div class="card mb-3 tk-wrap">
    <div class="card-header">
        <h5 class="mb-0">Support Tickets</h5>
        <div class="tk-meta">Every new ticket is also emailed to {{ config('mail.ticket_notify') }}. Changing status or replying emails the seller.</div>
    </div>
    <div class="card-body">
        @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

        <div class="tk-stats">
            <a href="{{ route('ticket') }}" class="tk-stat {{ request('status') ? '' : 'active' }}"><b>{{ $counts->sum() }}</b><span>All tickets</span></a>
            @foreach($statuses as $s)
                <a href="{{ route('ticket', ['status' => $s]) }}" class="tk-stat {{ request('status') === $s ? 'active' : '' }}"><b>{{ $counts[$s] ?? 0 }}</b><span>{{ $s }}</span></a>
            @endforeach
        </div>

        <form class="tk-filter" method="GET" action="{{ route('ticket') }}">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search ticket no, AWB, seller, message">
            <button class="btn btn-primary">Search</button>
            @if(request('q') || request('status'))<a href="{{ route('ticket') }}" class="btn btn-outline-secondary">Clear</a>@endif
        </form>

        <div class="tk-list">
            @forelse($tickets as $ticket)
                <div class="tk-item">
                    <div class="tk-top">
                        <div>
                            <div class="tk-no">{{ $ticket->ticket_id }} · {{ $ticket->subject ?: 'General' }}</div>
                            <div class="tk-meta">
                                AWB <b>{{ $ticket->order_id }}</b> ·
                                {{ $ticket->seller->name ?? 'Seller #' . $ticket->seller_id }}
                                @if($ticket->seller?->email) · {{ $ticket->seller->email }} @endif
                                @if($ticket->seller?->phone_number) · {{ $ticket->seller->phone_number }} @endif
                                · {{ $ticket->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                @if($ticket->mail_sent_at) · <span title="Emailed to support inbox">✉ emailed</span> @endif
                            </div>
                        </div>
                        <span class="tk-badge tk-{{ str_replace(' ', '', $ticket->status) }}">{{ $ticket->status }}</span>
                    </div>
                    <p class="tk-msg">{{ $ticket->message }}</p>
                    @if($ticket->admin_reply)
                        <div class="tk-reply-old"><b>Your reply</b> ({{ $ticket->replied_at?->timezone('Asia/Kolkata')->format('d M, h:i A') }}): {{ $ticket->admin_reply }}</div>
                    @endif
                    <form method="POST" action="{{ route('tickets.update-status', $ticket->id) }}" class="tk-form">
                        @csrf
                        @method('PATCH')
                        <textarea name="admin_reply" class="form-control" rows="1" placeholder="Reply to seller (optional)"></textarea>
                        <select name="status" class="form-select">
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" @selected($ticket->status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary">Update</button>
                    </form>
                </div>
            @empty
                <div class="tk-empty">No tickets found.</div>
            @endforelse
        </div>

        <div class="mt-3">{{ $tickets->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
