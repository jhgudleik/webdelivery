<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Личный кабинет пользователя.
 *
 * Доступен только авторизованным (routes/web.php middleware 'auth').
 * Позволяет менять имя, email, аватар, пароль.
 */
class ProfileController extends Controller
{
    /**
     * GET /profile — показать профиль.
     *
     * Отдаёт вьюху auth.profile с текущим пользователем.
     */
    public function profile(): View
    {
        return view('auth.profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * PATCH /profile — обновить имя, email, аватар.
     *
     * Валидация:
     * - name: обязательно, строка, макс 255;
     * - email: обязательно, email, уникально (кроме текущего пользователя);
     * - avatar: опционально, картинка jpg/jpeg/png/webp, макс 2 МБ.
     *
     * Если загружен новый аватар:
     *   1. Удаляется старый файл из storage/app/public/avatars/
     *   2. Новый сохраняется через store('avatars', 'public')
     *   3. Путь записывается в user->avatar
     *
     * Редирект обратно с флеш-сообщением 'profile-updated'.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'name.required' => 'Введите имя.',

            'email.required' => 'Введите email.',
            'email.email' => 'Введите корректный email.',
            'email.unique' => 'Этот email уже используется.',

            'avatar.image' => 'Файл должен быть изображением.',
            'avatar.mimes' => 'Разрешены JPG, JPEG, PNG и WEBP.',
            'avatar.max' => 'Размер аватара не должен превышать 2 МБ.',
        ]);

        /*
         * Обновляем имя и email.
         */
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        /*
         * Если пользователь загрузил новую аватарку:
         *
         * 1. Удаляем старую.
         * 2. Загружаем новую.
         * 3. Сохраняем путь к новой аватарке в БД.
         */
        if ($request->hasFile('avatar')) {

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request
                ->file('avatar')
                ->store('avatars', 'public');
        }

        $user->save();

        return redirect()
            ->route('profile')
            ->with('status', 'profile-updated');
    }

    /**
     * PUT /profile/password — смена пароля.
     *
     * Валидация:
     * - current_password: обязательно, должно совпадать с текущим
     *   (валидатор 'current_password' проверяет через Hash::check);
     * - password: обязательно, подтверждённое (confirmed), мин 8 символов.
     *
     * Хэшируется через Hash::make().
     * Редирект с 'password-updated'.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],

            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],
        ], [
            'current_password.required' => 'Введите текущий пароль.',
            'current_password.current_password' => 'Текущий пароль указан неверно.',

            'password.required' => 'Введите новый пароль.',
            'password.confirmed' => 'Пароли не совпадают.',
            'password.min' => 'Новый пароль должен содержать минимум 8 символов.',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('profile')
            ->with('status', 'password-updated');
    }

    /**
     * DELETE /profile/avatar — удалить аватар.
     *
     * Удаляет файл из storage/app/public/avatars/ и обнуляет поле в БД.
     * Редирект с 'avatar-deleted'.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);

            $user->avatar = null;
            $user->save();
        }

        return redirect()
            ->route('profile')
            ->with('status', 'avatar-deleted');
    }
}