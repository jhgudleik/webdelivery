<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'admin_comment',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * Recalculate and save the total price from items.
     */
    public function recalculateTotal(): void
    {
        $this->total_price = $this->items->sum(fn ($item) => $item->price * $item->quantity);
        $this->saveQuietly();
    }

    /**
     * Available statuses with Russian labels.
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

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

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
