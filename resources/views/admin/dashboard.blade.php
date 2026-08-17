@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="ssm-page-header">
        <div class="ssm-page-header__title">Welcome back{{ auth()->check() ? ', ' . explode(' ', auth()->user()->name)[0] : '' }}</div>
        <div class="ssm-page-header__subtitle">Here's what's happening across the site today, {{ now()->format('F j, Y') }}.</div>
    </div>

    <div class="ssm-section-label">Overview</div>
    <div class="row g-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon"><i class="bi bi-houses"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Properties</div>
                    <div class="ssm-stat-card__value">{{ $propertiesCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-gold"><i class="bi bi-person-lines-fill"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Leads</div>
                    <div class="ssm-stat-card__value">{{ $leadsCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-info"><i class="bi bi-person-badge"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Agents</div>
                    <div class="ssm-stat-card__value">{{ $agentsCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ssm-stat-card">
                <span class="ssm-stat-card__icon is-success"><i class="bi bi-envelope-paper"></i></span>
                <div>
                    <div class="ssm-stat-card__label">Inquiries</div>
                    <div class="ssm-stat-card__value">{{ $inquiriesCount }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="ssm-section-label">Analytics</div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="ssm-chart-card">
                <div class="ssm-chart-card__header">
                    <i class="bi bi-houses"></i>
                    <h6 class="fw-bold mb-0">Properties by Status</h6>
                </div>
                @php $maxProperty = max($propertyStatusCounts->max(), 1); @endphp
                @forelse (\App\Enums\PropertyStatus::cases() as $status)
                    <div class="ssm-bar-row">
                        <span class="ssm-bar-row__label">{{ $status->label() }}</span>
                        <span class="ssm-bar-row__track">
                            <span class="ssm-bar-row__fill" style="width: {{ ($propertyStatusCounts[$status->value] / $maxProperty) * 100 }}%"></span>
                        </span>
                        <span class="ssm-bar-row__value">{{ $propertyStatusCounts[$status->value] }}</span>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ssm-chart-card">
                <div class="ssm-chart-card__header">
                    <i class="bi bi-person-lines-fill"></i>
                    <h6 class="fw-bold mb-0">Leads by Status</h6>
                </div>
                @php $maxLead = max($leadStatusCounts->max(), 1); @endphp
                @foreach (\App\Enums\LeadStatus::cases() as $status)
                    <div class="ssm-bar-row">
                        <span class="ssm-bar-row__label">{{ $status->label() }}</span>
                        <span class="ssm-bar-row__track">
                            <span class="ssm-bar-row__fill" style="width: {{ ($leadStatusCounts[$status->value] / $maxLead) * 100 }}%"></span>
                        </span>
                        <span class="ssm-bar-row__value">{{ $leadStatusCounts[$status->value] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
