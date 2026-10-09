@extends('layouts.main')

@section('title', 'Админ — Заявка #' . $delivery->id)

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.deliveries.index') }}" class="text-decoration-none">Управление доставками</a></li>
            <li class="breadcrumb-item active">Заявка #{{ $delivery->id }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-file-invoice me-2"></i>Заявка #{{ $delivery->id }}
                    </h5>
                    <span class="badge {{ $delivery->status_badge }} fs-6">{{ $delivery->status_label }}</span>
                </div>
                <div class="card-body">
                    <h4 class="fw-bold mb-3">{{ $delivery->title }}</h4>

                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Клиент:</div>
                        <div class="col-sm-8 fw-semibold">
                            {{ $delivery->user->name ?? '—' }}
                            <small class="text-muted">({{ $delivery->user->email ?? '' }})</small>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Адрес:</div>
                        <div class="col-sm-8">{{ $delivery->delivery_address }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Телефон:</div>
                        <div class="col-sm-8">{{ $delivery->phone }}</div>
                    </div>
                    @if($delivery->description)
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Комментарий:</div>
                        <div class="col-sm-8">{{ $delivery->description }}</div>
                    </div>
                    @endif
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Создана:</div>
                        <div class="col-sm-8">{{ $delivery->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="card mt-4">
                <div class="card-header fw-bold">
                    <i class="fas fa-box me-1"></i>Товары ({{ $delivery->items->count() }})
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Товар</th>
                                    <th>Артикул</th>
                                    <th>Кол-во</th>
                                    <th>Цена</th>
                                    <th>Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($delivery->items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $item->product_title }}</td>
                                        <td class="text-muted">{{ $item->product_sku ?? '—' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->price, 2, '.', ' ') }} ₽</td>
                                        <td class="fw-bold">{{ number_format($item->price * $item->quantity, 2, '.', ' ') }} ₽</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Итого:</td>
                                    <td class="fw-bold fs-5 text-primary">{{ number_format($delivery->total_price, 2, '.', ' ') }} ₽</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Боковая панель: смена статуса -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header fw-bold"><i class="fas fa-cog me-1"></i>Изменить статус</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.deliveries.status', $delivery) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Статус</label>
                            <select class="form-select" name="status">
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ $delivery->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Комментарий</label>
                            <textarea class="form-control" name="admin_comment" rows="3">{{ $delivery->admin_comment ?? '' }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i>Сохранить
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="{{ route('admin.deliveries.destroy', $delivery) }}"
                          onsubmit="return confirm('Удалить заявку?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-1"></i>Удалить заявку
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
