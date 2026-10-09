@foreach($categories as $cat)
    @php
        $slug = $cat['slug'] ?? $cat['id'] ?? '';
        $hasChildren = !empty($cat['children']);
        $isActive = ($currentCategory ?? '') === $slug;
    @endphp
    <a href="{{ route('catalog.index', ['category' => $slug]) }}"
       class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}"
       style="padding-left: {{ ($depth ?? 0) * 1.2 + 0.5 }}rem;">
        @if($hasChildren)
            <i class="fas fa-chevron-right me-1" style="font-size: 0.6rem; opacity: 0.5;"></i>
        @else
            <i class="fas fa-circle me-1" style="font-size: 0.25rem; opacity: 0.3;"></i>
        @endif
        {{ $cat['title'] ?? '' }}
    </a>
    @if($hasChildren)
        @include('catalog.partials.category-tree', [
            'categories' => $cat['children'],
            'depth' => ($depth ?? 0) + 1,
        ])
    @endif
@endforeach