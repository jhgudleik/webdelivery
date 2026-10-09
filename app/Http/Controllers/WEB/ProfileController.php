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

class ProfileController extends Controller
{
    /**
     * Показать профиль пользователя.
     */
    public function profile(): View
    {
        return view('auth.profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Обновить имя, email и аватар.
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
     * Изменить пароль.
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
     * Удалить аватар.
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
