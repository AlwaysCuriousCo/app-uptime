{{-- Heartbeat bar: one coloured cell per recorded probe, oldest on the left.
     Expects $heartbeats (Collection of HealthCheckRecord) and optional $large. --}}
@if ($heartbeats->isEmpty())
    <p class="beats-empty">No probes recorded yet — the scheduled <code>health:check</code> run populates this.</p>
@else
    <div class="beats {{ ($large ?? false) ? 'beats--lg' : '' }}">
        @foreach ($heartbeats as $beat)
            <span
                class="beat {{ $beat->healthy ? 'beat--ok' : 'beat--fail' }}"
                title="{{ $beat->checked_at->toDayDateTimeString() }} — {{ $beat->message }}{{ $beat->response_time_ms !== null ? ' ('.$beat->response_time_ms.' ms)' : '' }}"
            ></span>
        @endforeach
    </div>
@endif
