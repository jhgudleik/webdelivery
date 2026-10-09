@extends('layouts.main')

@section('title', 'Каталог товаров')

@section('content')
    <div class="mb-4">
        <h1 class="fw-bold">
            <i class="fas fa-boxes-stacked me-2 text-primary"></i>Каталог товаров
        </h1>
        <p class="text-muted">Товары из магазина — выберите и создайте доставку</p>
    </div>

    <div class="row">
        <!-- Sidebar с категориями -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header fw-bold">
                    <i class="fas fa-th-list me-1"></i>Категории
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('catalog.index') }}"
                       class="list-group-item list-group-item-action {{ !$currentCategory ? 'active' : '' }}">
                        Все товары
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('catalog.index', ['category' => $cat['slug'] ?? $cat['id'] ?? '']) }}"
                           class="list-group-item list-group-item-action {{ $currentCategory === ($cat['slug'] ?? '') ? 'active' : '' }}">
                            {{ $cat['title'] ?? '' }}
                        </a>
                        @if(!empty($cat['children']))
                            @foreach($cat['children'] as $child)
                                <a href="{{ route('catalog.index', ['category' => $child['slug'] ?? $child['id'] ?? '']) }}"
                                   class="list-group-item list-group-item-action ps-4 {{ $currentCategory === ($child['slug'] ?? '') ? 'active' : '' }}">
                                    {{ $child['title'] ?? '' }}
                                </a>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Список товаров -->
        <div class="col-md-9">
            @if(!empty($products))
                <div class="row">
                    @foreach($products as $product)
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                @if(!empty($product['image']))
                                    <img src="{{ $product['image'] }}"
                                         class="card-img-top"
                                         alt="{{ $product['title'] ?? '' }}"
                                         style="height: 200px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">{{ $product['title'] ?? '' }}</h5>
                                    <p class="card-text text-muted small">
                                        {{ $product['category']['title'] ?? '' }}
                                    </p>
                                    @if(!empty($product['description']))
                                        <p class="card-text small">
                                            {{ \Illuminate\Support\Str::limit($product['description'], 80) }}
                                        </p>
                                    @endif
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-primary fs-5">
                                                {{ number_format($product['price'] ?? 0, 2, '.', ' ') }} ₽
                                            </span>
                                            @if(($product['quantity'] ?? 0) > 0)
                                                <span class="badge bg-success">В наличии</span>
                                            @else
                                                <span class="badge bg-secondary">Нет в наличии</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('catalog.show', $product['id'] ?? 0) }}" class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-eye me-1"></i>Подробнее
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Простая пагинация --}}
                @if(!empty($meta['last_page']) && $meta['last_page'] > 1)
                    <nav aria-label="Навигация по страницам" class="mt-3">
                        <ul class="pagination justify-content-center">
                            @for($i = 1; $i <= $meta['last_page']; $i++)
                                <li class="page-item {{ ($meta['current_page'] ?? 1) == $i ? 'active' : '' }}">
                                    <a class="page-link" href="{{ route('catalog.index', array_merge(request()->query(), ['page' => $i])) }}">{{ $i }}</a>
                                </li>
                            @endfor
                        </ul>
                    </nav>
                @endif
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Товары не найдены. Попробуйте позже или проверьте подключение к магазину.
                </div>
            @endif
        </div>
    </div>
@endsection
