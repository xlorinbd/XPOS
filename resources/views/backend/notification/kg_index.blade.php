@extends('backend.layout.main')
@section('content')
<section>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-1">Notifications</h4>
            <form method="POST" action="{{ route('kg.notifications.readall') }}">@csrf
                <button type="submit" class="btn btn-sm btn-outline-dark">Mark all as read</button>
            </form>
        </div>
        <p class="text-muted">Click a notification to open the exact record.</p>
    </div>
    <div class="table-responsive mt-2">
        <table class="table">
            <thead><tr><th style="width:170px">When</th><th>Message</th><th style="width:110px">Status</th></tr></thead>
            <tbody>
            @forelse($items as $n)
                <tr>
                    <td>{{ $n->created_at->format('d/m/Y h:i A') }}</td>
                    <td><a href="{{ route('kg.notifications.go', $n->id) }}" class="{{ $n->read_at ? 'text-muted' : 'font-weight-bold' }}">{{ $n->message }}</a></td>
                    <td>@if($n->read_at)<span class="text-muted">Read</span>@else<span class="badge badge-warning">New</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">Nothing yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
