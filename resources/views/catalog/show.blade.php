@extends('layouts.main')

@section('title', $product['title'] ?? 'Товар')

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('catalog.index') }}" class="text-decoration-none">Каталог</a></li>
            <li class="breadcrumb-item active">{{ $product['title'] ?? '' }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-5 mb-4">
            @if(!empty($product['image']))
                <img src="{{ $product['image'] }}" class="img-fluid rounded-3 shadow-sm" alt="{{ $product['title'] ?? '' }}" style="width: 100%; object-fit: cover;">
            @else
                <div class="bg-light d-flex align-items-center justify-content-center rounded-3" style="height: 400px;">
                    <i class="fas fa-image fa-5x text-muted"></i>
                </div>
            @endif
        </div>

        <div class="col-md-7">
            <h2 class="fw-bold mb-2">{{ $product['title'] ?? '' }}</h2>
            <p class="text-muted mb-3">{{ $product['category']['title'] ?? '' }}</p>

            <div class="mb-3">
                <span class="fs-3 fw-bold text-primary">{{ number_format($product['price'] ?? 0, 2, '.', ' ') }} ₽</span>
            </div>

            <div class="mb-3">
                @if(($product['quantity'] ?? 0) > 0)
                    <span class="badge bg-success fs-6"><i class="fas fa-check me-1"></i>В наличии ({{ $product['quantity'] }} шт.)</span>
                @else
                    <span class="badge bg-secondary fs-6"><i class="fas fa-times me-1"></i>Нет в наличии</span>
                @endif
            </div>

            @if(!empty($product['description']))
                <div class="mb-4">
                    <h5 class="fw-bold">Описание</h5>
                    <p class="text-muted">{{ $product['description'] }}</p>
                </div>
            @endif

            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('deliveries.create') }}?product_title={{ urlencode($product['title'] ?? '') }}&price={{ urlencode($product['price'] ?? '') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-truck me-2"></i>Заказать доставку
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Войдите для заказа
                    </a>
                @endauth
                <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-1"></i>Назад
                </a>
            </div>
        </div>
    </div>
@endsection
