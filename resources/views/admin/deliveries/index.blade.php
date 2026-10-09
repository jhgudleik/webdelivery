@extends('layouts.main')

@section('title', 'Админ — Заявки на доставку')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="fas fa-shield-halved me-2 text-warning"></i>Управление доставками
        </h2>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Поиск</label>
                    <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}"
                           placeholder="Название, адрес, телефон, имя...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Статус</label>
                    <select class="form-select" name="status">
                        <option value="">Все статусы</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ ($currentStatus ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Фильтровать
                    </button>
                    <a href="{{ route('admin.deliveries.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Таблица -->
    @if($deliveries->count())
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Название</th>
                            <th>Клиент</th>
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
                                <td>
                                    <div>{{ $delivery->user->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $delivery->user->email ?? '' }}</small>
                                </td>
                                <td class="text-muted">{{ Str::limit($delivery->delivery_address, 25) }}</td>
                                <td>{{ $delivery->items->count() }}</td>
                                <td class="fw-bold">{{ number_format($delivery->total_price, 2, '.', ' ') }} ₽</td>
                                <td><span class="badge {{ $delivery->status_badge }}">{{ $delivery->status_label }}</span></td>
                                <td class="text-muted">{{ $delivery->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.deliveries.show', $delivery) }}" class="btn btn-sm btn-outline-primary" title="Просмотр">
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
            {{ $deliveries->withQueryString()->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>Заявки не найдены.
        </div>
    @endif
@endsection
