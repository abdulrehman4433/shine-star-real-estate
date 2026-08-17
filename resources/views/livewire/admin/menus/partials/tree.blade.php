@php $item = $node['item']; @endphp

<li class="list-group-item" x-sort:item="{{ $item->id }}" wire:key="menu-item-{{ $item->id }}">
    <div class="d-flex align-items-center gap-2">
        <span x-sort:handle><i class="bi bi-grip-vertical"></i></span>

        <span class="flex-grow-1 {{ $item->is_active ? '' : 'text-muted text-decoration-line-through' }}">
            {{ $item->label }}
            <span class="text-muted small">{{ $item->resolvedUrl() }}</span>
        </span>

        @unless ($item->is_active)
            <span class="badge text-bg-secondary">Hidden</span>
        @endunless

        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" wire:click="toggleItemActive({{ $item->id }})">
                <i class="bi {{ $item->is_active ? 'bi-eye-slash' : 'bi-eye' }}"></i> {{ $item->is_active ? 'Hide' : 'Show' }}
            </button>
            <button type="button" class="btn btn-outline-primary" wire:click="editItem({{ $item->id }})">
                <i class="bi bi-pencil"></i> Edit
            </button>
            <button type="button" class="btn btn-outline-danger"
                @click="$store.confirm.open({ message: 'Delete \'{{ $item->label }}\'? Its children will move to top level.' }).then(ok => ok && $wire.deleteItem({{ $item->id }}))">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </div>

    @if (! empty($node['children']))
        <ul class="list-group list-group-flush mt-2 ms-4" x-sort="(id, position) => $wire.reorder(id, position)">
            @foreach ($node['children'] as $child)
                @include('livewire.admin.menus.partials.tree', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
