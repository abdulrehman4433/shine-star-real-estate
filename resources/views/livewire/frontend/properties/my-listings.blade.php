<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Listings</h1>
        <a href="{{ route('agent.listings.create') }}" class="btn btn-primary">+ New Listing</a>
    </div>

    @if ($properties->isEmpty())
        <div class="alert alert-info">You haven't listed any properties yet.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($properties as $property)
                        <tr>
                            <td>
                                <a href="{{ route('properties.show', $property) }}" class="text-decoration-none">
                                    {{ $property->title }}
                                </a>
                                @if ($property->is_featured)
                                    <span class="badge text-bg-warning">Featured</span>
                                @endif
                            </td>
                            <td>{{ $property->category->name ?? '' }}</td>
                            <td>{{ $property->type->name ?? '' }}</td>
                            <td>{{ $property->formatted_price }}</td>
                            <td>
                                <span class="badge {{ $property->statusEnum()->badgeClass() }}">
                                    {{ $property->statusEnum()->label() }}
                                </span>
                                @if ($property->status === 'rejected' && $property->rejection_reason)
                                    <div class="text-muted small">{{ $property->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('agent.listings.edit', $property) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    @click="$store.confirm.open({ message: 'Delete \'{{ $property->title }}\'? This cannot be undone.' }).then(ok => ok && $wire.delete({{ $property->id }}))">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $properties->links() }}
    @endif
</div>
