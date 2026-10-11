<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Пользователь сервиса доставки.
 *
 * Роли отдельной таблицей не заведены (ограничение MVP):
 * роль определяет булево поле is_admin:
 *   false — обычный пользователь (по умолчанию),
 *   true  — администратор (доступ в /admin/*, проверяет CheckIfAdmin).
 *
 * Модель реализует JWTSubject — нужно для API-авторизации
 * через tymon/jwt-auth (guard "api").
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Поля, разрешённые для массового заполнения.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',    // путь к файлу в storage/app/public/avatars
        'is_admin',  // роль: администратор или обычный пользователь
    ];

    /**
     * Поля, скрытые при сериализации (не попадут в JSON).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Приведение типов.
     * 'password' => 'hashed' — Laravel сам хеширует пароль при присвоении,
     * поэтому Hash::make() в контроллерах не обязателен.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    // ─── JWT (tymon/jwt-auth) ───────────────────────────

    /**
     * Идентификатор, который попадёт в claim "sub" токена.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Дополнительные claims токена: кладём флаг админа,
     * чтобы API-клиент мог узнать роль без лишнего запроса.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'is_admin' => $this->is_admin,
        ];
    }

    // ─── Связи ──────────────────────────────────────────

    /**
     * Заявки на доставку этого пользователя.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}
