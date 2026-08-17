@php
    $failCount = collect($checks)->where('status', 'fail')->count();
    $warningCount = collect($checks)->where('status', 'warning')->count();
    $passCount = collect($checks)->where('status', 'pass')->count();
@endphp
<div>
    @include('admin.partials.breadcrumb', ['items' => [
        ['label' => 'Settings', 'route' => 'admin.settings.index'],
        ['label' => 'System Requirements Check'],
    ]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">System Requirements Check</div>
            <div class="ssm-page-header__subtitle">Run this on a new server right after copying the project files — before importing a database backup.</div>
        </div>
        <button type="button" wire:click="$refresh" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Re-check
        </button>
    </div>

    @if ($failCount > 0)
        <div class="alert alert-danger">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>{{ $failCount }} {{ Str::plural('problem', $failCount) }} found.</strong>
            Fix the items marked "Fail" below before importing a database or expecting the site to work correctly.
        </div>
    @elseif ($warningCount > 0)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Everything required passes, but {{ $warningCount }} {{ Str::plural('item', $warningCount) }} need attention (see "Warning" rows below).
        </div>
    @else
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-1"></i>
            All checks pass — this server is ready.
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 90px;">Status</th>
                        <th>Check</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td>
                                @if ($check['status'] === 'pass')
                                    <span class="badge text-bg-success">Pass</span>
                                @elseif ($check['status'] === 'warning')
                                    <span class="badge text-bg-warning">Warning</span>
                                @else
                                    <span class="badge text-bg-danger">Fail</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $check['label'] }}</td>
                            <td>
                                <div class="small">{{ $check['detail'] }}</div>
                                @if ($check['guidance'])
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-arrow-return-right"></i> {{ $check['guidance'] }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3">
        {{ $passCount }} passing, {{ $warningCount }} warning{{ $warningCount === 1 ? '' : 's' }}, {{ $failCount }} failing —
        out of {{ count($checks) }} checks. See <code>MIGRATION_GUIDE.md</code> in the project root for the full
        setup walkthrough, and <a href="{{ route('admin.settings.backup') }}">Backup &amp; Migration</a> to download
        the database/media backups once this server is ready.
    </p>
</div>
