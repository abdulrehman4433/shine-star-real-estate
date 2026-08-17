<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">My Properties</h1>
            <p class="text-muted mb-0 small">
                Browse every active listing — and manage the properties you've submitted.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('my.properties.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Property
            </a>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-secondary" title="Log out">
                    <i class="bi bi-box-arrow-right me-1"></i> Log out
                </button>
            </form>
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

        <div class="col-md-3">
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
                <div class="col-md-6 col-lg-4">
                    @include('livewire.frontend.properties.partials.card', ['property' => $property])

                    @if ($property->user_id === auth()->id())
                        @include('livewire.frontend.properties.partials.owner-actions', ['property' => $property])
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($properties as $property)
                <div>
                    @include('livewire.frontend.properties.partials.card', ['property' => $property, 'horizontal' => true])

                    @if ($property->user_id === auth()->id())
                        @include('livewire.frontend.properties.partials.owner-actions', ['property' => $property])
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-4">
        {{ $properties->links() }}
    </div>
</div>
