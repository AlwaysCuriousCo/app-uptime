@extends('uptime::layout')

@section('title', $label.' — health detail')

@section('body')
    @php
        $statusClass = $latest === null ? 'idle' : ($latest->healthy ? 'ok' : 'fail');
        $statusLabel = $latest === null ? 'No data' : ($latest->healthy ? 'Up' : 'Down');
    @endphp

    <a class="back-link" href="{{ route('uptime.index') }}">&larr; All health checks</a>

    @if (session('checkedResult'))
        @php $checked = session('checkedResult'); @endphp
        <div class="flash flash--{{ $checked['healthy'] ? 'ok' : 'fail' }}">
            {{ $checked['healthy'] ? 'Probe passed' : 'Probe failed' }} — {{ $checked['message'] }}
        </div>
    @endif

    <div class="detail-head">
        <div class="row">
            <span class="dot dot--{{ $statusClass }}"></span>
            <div>
                <h1>{{ $label }}</h1>
                <p class="subtle">
                    @if ($latest)
                        {{ $latest->message }} · checked {{ $latest->checked_at->diffForHumans() }}
                    @else
                        Awaiting the first scheduled probe.
                    @endif
                </p>
            </div>
        </div>
        <div class="detail-actions">
            <span class="badge badge--{{ $statusClass }}">{{ $statusLabel }}</span>
            <form method="POST" action="{{ route('uptime.check', $key) }}">
                @csrf
                <button type="submit" class="check-btn">Check now</button>
            </form>
        </div>
    </div>

    <div class="section-title">Heartbeat</div>
    @include('uptime::_heartbeats', ['heartbeats' => $heartbeats, 'large' => true])

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Response</div>
            <div class="stat-value">{{ $latest?->response_time_ms !== null ? $latest->response_time_ms.' ms' : '—' }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Avg · 24h</div>
            <div class="stat-value">{{ $avgResponseMs !== null ? $avgResponseMs.' ms' : '—' }}</div>
        </div>
        @foreach ($uptime as $window => $pct)
            <div class="stat">
                <div class="stat-label">Uptime · {{ $window }}</div>
                <div class="stat-value">{{ $pct !== null ? $pct.'%' : '—' }}</div>
            </div>
        @endforeach
    </div>

    @if ($chart)
        <div class="section-title">Response time · last {{ $heartbeats->count() }} probes</div>
        <svg class="chart" viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" preserveAspectRatio="none" role="img" aria-label="Response time chart">
            <polygon points="{{ $chart['area'] }}" fill="rgba(34, 197, 94, .12)" />
            <polyline points="{{ $chart['line'] }}" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
        </svg>
        <p class="subtle" style="display:flex;justify-content:space-between;margin-top:.35rem;">
            <span>min {{ $chart['min'] }} ms</span>
            <span>max {{ $chart['max'] }} ms</span>
        </p>
    @endif

    @if ($footnote !== '')
        <p class="footnote">{!! $footnote !!}</p>
    @endif
@endsection
