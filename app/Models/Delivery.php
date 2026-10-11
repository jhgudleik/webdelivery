<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Заявка на доставку.
 *
 * Создаётся пользователем, содержит адрес, телефон и набор товаров
 * (DeliveryItem). Товары в заявке — снимок данных из магазина
 * (название, цена на момент заказа) плюс product_id для связи
 * с товаром магазина при списании остатков.
 *
 * Жизненный цикл статуса:
 *   new → confirmed → shipped → delivered
 *   любой статус → cancelled
 *
 * Редактировать заявку может только владелец и только в статусе "new"
 * (проверяется в контроллерах, здесь ограничений нет).
 *
 * Удаление мягкое (SoftDeletes): запись остаётся в БД с deleted_at,
 * админ видит удалённые заявки через withTrashed().
 */
class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'delivery_address',
        'phone',
        'status',
        'total_price',
        'admin_comment', // комментарий администратора (виден пользователю)
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    // ─── Связи ──────────────────────────────────────────

    /**
     * Клиент, создавший заявку.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Позиции (товары) заявки.
     */
    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    // ─── Бизнес-логика ──────────────────────────────────

    /**
     * Пересчитать итоговую сумму заявки из позиций.
     *
     * Вызывается после создания/обновления товаров заявки.
     * saveQuietly() — чтобы не дергать события модели повторно.
     */
    public function recalculateTotal(): void
    {
        $this->total_price = $this->items->sum(fn ($item) => $item->price * $item->quantity);
        $this->saveQuietly();
    }

    /**
     * Словарь статусов: код → русское название.
     * Используется в селектах админки и для вывода.
     */
    public static function statuses(): array
    {
        return [
            'new'       => 'Новая',
            'confirmed' => 'Подтверждена',
            'shipped'   => 'Отправлена',
            'delivered' => 'Доставлена',
            'cancelled' => 'Отменена',
        ];
    }

    /**
     * Акцессор: русское название текущего статуса.
     * Во вьюхах: $delivery->status_label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    /**
     * Акцессор: CSS-класс Bootstrap-бейджа для текущего статуса.
     * Во вьюхах: $delivery->status_badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new'       => 'bg-info',
            'confirmed' => 'bg-primary',
            'shipped'   => 'bg-warning text-dark',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
            default     => 'bg-secondary',
        };
    }
}
