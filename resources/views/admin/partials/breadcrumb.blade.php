@php($items = $items ?? [])

@if (! empty($items))
    <nav aria-label="breadcrumb" class="ssm-breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.dashboard') }}" title="Dashboard"><i class="bi bi-house-door"></i></a>
            </li>
            @foreach ($items as $item)
                @if (! empty($item['route']) && ! $loop->last)
                    <li class="breadcrumb-item">
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}">{{ $item['label'] }}</a>
                    </li>
                @elseif ($loop->last)
                    <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                @else
                    <li class="breadcrumb-item text-muted">{{ $item['label'] }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
