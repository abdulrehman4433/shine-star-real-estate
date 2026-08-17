<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Leads</h1>

        <select wire:model.live="statusFilter" class="form-select w-auto">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($leads->isEmpty())
        <div class="alert alert-info">No leads assigned to you yet.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Property</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leads as $lead)
                        <tr>
                            <td>{{ $lead->name }}</td>
                            <td>{{ $lead->contact }}</td>
                            <td>{{ $lead->property->title ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $lead->statusEnum()->badgeClass() }}">
                                    {{ $lead->statusEnum()->label() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">
                                    View
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
