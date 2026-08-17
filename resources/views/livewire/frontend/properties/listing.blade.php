<div class="container py-5">
    <h1 class="h3 mb-4">Properties</h1>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <label class="form-label">Keyword or Location</label>
            <input type="text" wire:model.live.debounce.500ms="keyword" class="form-control" placeholder="Search by title, city, or address">
        </div>
    </div>

    <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select wire:model.live="category" class="form-select">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @foreach ($cat->children as $child)
                        <option value="{{ $child->id }}">&nbsp;&nbsp;— {{ $child->name }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Type</label>
            <select wire:model.live="type" class="form-select">
                <option value="">All types</option>
                @foreach ($types as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Min price</label>
            <input type="number" wire:model.live.debounce.500ms="min_price" class="form-control" placeholder="Any">
        </div>

        <div class="col-md-2">
            <label class="form-label">Max price</label>
            <input type="number" wire:model.live.debounce.500ms="max_price" class="form-control" placeholder="Any">
        </div>

        <div class="col-md-1">
            <label class="form-label">Beds</label>
            <select wire:model.live="bedrooms" class="form-select">
                <option value="">Any</option>
                @foreach ([1, 2, 3, 4, 5] as $n)
                    <option value="{{ $n }}">{{ $n }}+</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">City</label>
            <input type="text" wire:model.live.debounce.500ms="city" class="form-control" placeholder="Any city">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-secondary">
                Clear filters
            </button>
            <span class="text-muted small ms-2">{{ $properties->total() }} result(s)</span>
        </div>

        <div class="btn-group btn-group-sm">
            <button type="button" wire:click="$set('layout', 'grid')"
                class="btn {{ $layout === 'grid' ? 'btn-primary' : 'btn-outline-secondary' }}">
                Grid
            </button>
            <button type="button" wire:click="$set('layout', 'list')"
                class="btn {{ $layout === 'list' ? 'btn-primary' : 'btn-outline-secondary' }}">
                List
            </button>
        </div>
    </div>

    @if ($properties->isEmpty())
        <div class="alert alert-info">No properties match your filters yet.</div>
    @elseif ($layout === 'grid')
        <div class="row g-4">
            @foreach ($properties as $property)
                <div class="col-md-4">
                    @include('livewire.frontend.properties.partials.card', ['property' => $property])
                </div>
            @endforeach
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($properties as $property)
                @include('livewire.frontend.properties.partials.card', ['property' => $property, 'horizontal' => true])
            @endforeach
        </div>
    @endif

    <div class="mt-4">
        {{ $properties->links() }}
    </div>
</div>
