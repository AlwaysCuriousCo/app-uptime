@extends('uptime::layout')

@section('title', $healthy ? 'Application up' : 'Application down')

@section('body')
    <div class="row">
        <span class="dot {{ $healthy ? 'dot--ok' : 'dot--fail' }}"></span>
        <div>
            <h1>{{ $healthy ? 'Application up' : 'Application down' }}</h1>
            <p class="subtle">
                {{ $healthy ? 'All dependency checks passed.' : 'One or more dependency checks failed.' }}
            </p>
        </div>
    </div>

    <ul class="checks">
        @foreach ($checks as $check)
            @php $latest = $check['latest']; @endphp
            <li>
                <span class="dot {{ $latest === null ? 'dot--idle' : ($latest->healthy ? 'dot--ok' : 'dot--fail') }}"></span>
                <div class="check-body">
                    <div class="check-head">
                        <span class="check-label">{{ $check['label'] }}</span>
                        @if ($check['uptime'] !== null)
                            <span class="check-uptime">{{ $check['uptime'] }}% uptime · 24h</span>
                        @endif
                    </div>
                    <div class="check-message">{{ $latest?->message ?? 'Awaiting the first scheduled probe.' }}</div>
                    @include('uptime::_heartbeats', ['heartbeats' => $check['heartbeats']])
                </div>
                <a class="details-btn" href="{{ route('uptime.detail', $check['key']) }}">details</a>
            </li>
        @endforeach
    </ul>
@endsection
