@extends('layouts.main')

@section('title', 'WebDelivery — Сервис доставки')

@section('content')

<!-- Герой-секция -->
<div class="text-center py-5 mb-4" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px; color: #fff;">
    <h1 class="fw-bold mb-3"><i class="fas fa-truck-fast me-2"></i>Сервис доставки</h1>
    <p class="lead mb-4" style="color: rgba(255,255,255,0.8);">
        Заказывайте товары из магазинов — мы доставим прямо к вам
    </p>
    <div class="d-flex gap-3 justify-content-center">
        <a href="{{ route('catalog.index') }}" class="btn btn-light btn-lg px-4">
            <i class="fas fa-boxes-stacked me-2"></i>Каталог товаров
        </a>
        @guest
            <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-user-plus me-2"></i>Регистрация
            </a>
        @else
            <a href="{{ route('deliveries.create') }}" class="btn btn-outline-light btn-lg px-4">
                <i class="fas fa-plus me-2"></i>Создать доставку
            </a>
        @endguest
    </div>
</div>

<!-- Как это работает -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="card-body">
                <div class="mb-3" style="font-size: 2.5rem; color: #e94560;">
                    <i class="fas fa-magnifying-glass"></i>
                </div>
                <h5 class="fw-bold">1. Выберите товар</h5>
                <p class="text-muted">Просмотрите каталог и выберите нужные товары из магазина</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="card-body">
                <div class="mb-3" style="font-size: 2.5rem; color: #e94560;">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5 class="fw-bold">2. Оформите доставку</h5>
                <p class="text-muted">Создайте заявку на доставку, укажите адрес и товары</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="card-body">
                <div class="mb-3" style="font-size: 2.5rem; color: #e94560;">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <h5 class="fw-bold">3. Получите доставку</h5>
                <p class="text-muted">Отслеживайте статус и получите товар по указанному адресу</p>
            </div>
        </div>
    </div>
</div>

<!-- Популярные товары из магазина -->
@if(!empty($products))
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold"><i class="fas fa-fire me-2 text-danger"></i>Товары из магазина</h3>
            <a href="{{ route('catalog.index') }}" class="btn btn-outline-primary btn-sm">
                Все товары <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row g-4">
            @foreach($products as $product)
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card h-100">
                        @if(!empty($product['image']))
                            <img src="{{ $product['image'] }}" class="card-img-top" alt="{{ $product['title'] ?? '' }}" style="height: 160px; object-fit: cover;">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                <i class="fas fa-image fa-2x text-muted"></i>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column p-3">
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">{{ $product['title'] ?? '' }}</h6>
                            <small class="text-muted mb-2">{{ $product['category']['title'] ?? '' }}</small>
                            <div class="mt-auto">
                                <span class="fw-bold text-primary">
                                    {{ number_format($product['price'] ?? 0, 2, '.', ' ') }} ₽
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Каталог товаров временно недоступен. Попробуйте позже.
    </div>
@endif

@endsection
