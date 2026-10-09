@extends('layouts.main')

@section('title', 'Мои доставки')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="fas fa-truck me-2 text-primary"></i>Мои доставки
        </h2>
        <a href="{{ route('deliveries.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Новая заявка
        </a>
    </div>

    @if($deliveries->count())
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Адрес</th>
                            <th>Товаров</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveries as $delivery)
                            <tr>
                                <td>{{ $delivery->id }}</td>
                                <td class="fw-semibold">{{ $delivery->title }}</td>
                                <td class="text-muted">{{ Str::limit($delivery->delivery_address, 30) }}</td>
                                <td>{{ $delivery->items->count() }}</td>
                                <td class="fw-bold">{{ number_format($delivery->total_price, 2, '.', ' ') }} ₽</td>
                                <td><span class="badge {{ $delivery->status_badge }}">{{ $delivery->status_label }}</span></td>
                                <td class="text-muted">{{ $delivery->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('deliveries.show', $delivery) }}" class="btn btn-sm btn-outline-primary" title="Просмотр">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $deliveries->links() }}
        </div>
    @else
        <div class="text-center py-5">
            <div class="mb-3" style="font-size: 3rem; color: #ccc;">
                <i class="fas fa-truck"></i>
            </div>
            <h4 class="text-muted">У вас пока нет заявок на доставку</h4>
            <p class="text-muted">Создайте первую заявку, чтобы заказать товары</p>
            <a href="{{ route('deliveries.create') }}" class="btn btn-primary mt-2">
                <i class="fas fa-plus me-1"></i>Создать заявку
            </a>
        </div>
    @endif
@endsection
