<div>
    @include('admin.partials.breadcrumb', ['items' => [['label' => 'CRM'], ['label' => 'Leads']]])

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div class="ssm-page-header mb-0">
            <div class="ssm-page-header__title">Leads</div>
            <div class="ssm-page-header__subtitle">Track and assign inquiries to your agents.</div>
        </div>

        <select wire:model.live="statusFilter" class="form-select w-auto">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="card">
        <div class="card-body">
            @if ($leads->isEmpty())
                <div class="ssm-empty-state">
                    <i class="bi bi-person-lines-fill"></i>
                    <p>No leads yet. New inquiries will automatically appear here.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Property</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leads as $lead)
                                <tr wire:key="lead-{{ $lead->id }}">
                                    <td>
                                        <a href="{{ route('leads.show', $lead) }}" class="text-decoration-none">
                                            {{ $lead->name }}
                                        </a>
                                    </td>
                                    <td>{{ $lead->contact }}</td>
                                    <td>{{ $lead->property->title ?? '—' }}</td>
                                    <td>{{ $lead->source }}</td>
                                    <td>
                                        <span class="badge {{ $lead->statusEnum()->badgeClass() }}">
                                            {{ $lead->statusEnum()->label() }}
                                        </span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm"
                                            wire:change="assign({{ $lead->id }}, $event.target.value)">
                                            <option value="">— Unassigned —</option>
                                            @foreach ($agents as $agent)
                                                <option value="{{ $agent->id }}" @selected($lead->assigned_to === $agent->id)>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $leads->links() }}
            @endif
        </div>
    </div>
</div>
