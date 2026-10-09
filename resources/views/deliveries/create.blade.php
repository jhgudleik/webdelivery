@extends('layouts.main')

@section('title', 'Новая заявка на доставку')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="fas fa-plus-circle me-2 text-primary"></i>Новая заявка на доставку
        </h2>
        <a href="{{ route('deliveries.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Назад
        </a>
    </div>

    <form method="POST" action="{{ route('deliveries.store') }}" id="deliveryForm">
        @csrf

        <div class="row">
            <!-- Основная информация -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header fw-bold">
                        <i class="fas fa-info-circle me-1"></i>Информация о доставке
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Название *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="delivery_address" class="form-label fw-semibold">Адрес доставки *</label>
                            <textarea class="form-control @error('delivery_address') is-invalid @enderror"
                                      id="delivery_address" name="delivery_address" rows="2" required>{{ old('delivery_address') }}</textarea>
                            @error('delivery_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Телефон *</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Комментарий</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center fw-bold">
                        <span><i class="fas fa-box me-1"></i>Товары *</span>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="fas fa-plus me-1"></i>Добавить товар
                        </button>
                    </div>
                    <div class="card-body" id="itemsContainer">
                        {{-- Сюда добавляются товары через JS --}}
                    </div>
                    @error('items')
                        <div class="px-3 pb-3 text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('deliveries.index') }}" class="btn btn-outline-secondary">Отмена</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-paper-plane me-1"></i>Создать заявку
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        let itemIndex = 0;

        // Pre-fill from query params (when coming from catalog)
        const params = new URLSearchParams(window.location.search);
        const preTitle = params.get('product_title');
        const prePrice = params.get('price');

        function addItem(title = '', sku = '', quantity = 1, price = '') {
            const html = `
                <div class="border rounded-3 p-3 mb-3 item-row" data-index="${itemIndex}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold text-muted small">Товар #${itemIndex + 1}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" class="form-control form-control-sm"
                                   name="items[${itemIndex}][product_title]"
                                   placeholder="Название товара" value="${title}" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm"
                                   name="items[${itemIndex}][product_sku]"
                                   placeholder="Артикул" value="${sku}">
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control form-control-sm"
                                   name="items[${itemIndex}][quantity]"
                                   placeholder="Кол-во" value="${quantity}" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control form-control-sm"
                                   name="items[${itemIndex}][price]"
                                   placeholder="Цена ₽" step="0.01" min="0" value="${price}" required>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
            itemIndex++;
        }

        document.getElementById('addItemBtn').addEventListener('click', () => addItem());

        document.getElementById('itemsContainer').addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                e.target.closest('.item-row').remove();
            }
        });

        // Add first item (pre-filled if coming from catalog)
        addItem(preTitle || '', '', 1, prePrice || '');
    </script>
    @endpush
@endsection
