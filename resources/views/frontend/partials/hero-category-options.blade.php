<option value="">All Category</option>
@foreach ($categories as $cat)
    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
    @foreach ($cat->children as $child)
        <option value="{{ $child->id }}">&nbsp;&nbsp;— {{ $child->name }}</option>
    @endforeach
@endforeach
