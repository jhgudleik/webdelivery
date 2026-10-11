<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Позиция (строка товара) в заявке на доставку.
 *
 * Это снимок данных товара на момент оформления заявки:
 * название и цена хранятся локально, чтобы изменения в магазине
 * не ломали старые заявки.
 *
 * product_id — ID товара во внешнем магазине. Может быть null,
 * если товар добавлен вручную (не из каталога). Используется
 * для списания остатков при подтверждении заявки
 * (ShopApiService::reduceDeliveryStock).
 */
class DeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'product_id',    // ID товара в магазине (null = товар введён вручную)
        'product_title',
        'product_sku',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price'    => 'decimal:2',
    ];

    /**
     * Заявка, которой принадлежит позиция.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
