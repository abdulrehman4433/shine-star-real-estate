<div class="card border-top-0 rounded-top-0 mt-0" style="box-shadow: none;">
    <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-primary">Yours</span>
            <span class="badge {{ $property->statusEnum()->badgeClass() }}">
                {{ $property->statusEnum()->label() }}
            </span>
            @if ($property->status === 'rejected' && $property->rejection_reason)
                <span class="text-muted small" title="{{ $property->rejection_reason }}">
                    <i class="bi bi-exclamation-circle"></i> {{ Str::limit($property->rejection_reason, 40) }}
                </span>
            @endif
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('my.properties.edit', $property) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger"
                @click="$store.confirm.open({ message: 'Delete \'{{ $property->title }}\'? This cannot be undone.' }).then(ok => ok && $wire.delete({{ $property->id }}))">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </div>
    </div>
</div>
