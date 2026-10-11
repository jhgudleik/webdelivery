<?php

/*
|--------------------------------------------------------------------------
| Web Routes — WebDelivery
|--------------------------------------------------------------------------
|
| Маршруты веб-интерфейса сервиса доставки.
| Товары в каталоге загружаются из внешнего магазина по API
| (см. App\Services\ShopApiService), а заявки хранятся в локальной БД.
|
*/

use App\Http\Controllers\WEB\Admin\DeliveryController as AdminDeliveryController;
use App\Http\Controllers\WEB\Auth\LoginController;
use App\Http\Controllers\WEB\Auth\RegisterController;
use App\Http\Controllers\WEB\CatalogController;
use App\Http\Controllers\WEB\DeliveryController;
use App\Http\Controllers\WEB\HomeController;
use App\Http\Controllers\WEB\ProfileController;
use Illuminate\Support\Facades\Route;

// ─── Главная страница ──────────────────────────────
// Показывает несколько товаров из магазина (через API) и кнопки-ссылки.
Route::get('/', [HomeController::class, 'index'])->name('home');

// ─── Каталог товаров внешнего магазина ─────────────
// Данные приходят из ShopApiService — локальной таблицы товаров нет.
// index: список с пагинацией и фильтром ?category=slug
// show:  карточка одного товара по ID из магазина
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');

// ─── Аутентификация (сессии) ───────────────────────
// Стандартные контроллеры laravel/ui с трейтами AuthenticatesUsers / RegistersUsers.
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// ─── Личный кабинет (только авторизованные) ────────
Route::middleware('auth')->group(function () {
    // Просмотр и редактирование профиля (имя, email, аватар)
    Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Смена пароля (текущий + новый + подтверждение)
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Удаление аватара (файл стирается из storage/app/public/avatars)
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
});

// ─── Заявки на доставку (только авторизованные) ────
// Пользователь видит и изменяет только СВОИ заявки.
// Редактирование возможно только в статусе "new" (проверяется в контроллере).
Route::middleware('auth')->group(function () {
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::get('/deliveries/{delivery}/edit', [DeliveryController::class, 'edit'])->name('deliveries.edit');
    Route::put('/deliveries/{delivery}', [DeliveryController::class, 'update'])->name('deliveries.update');
    Route::delete('/deliveries/{delivery}', [DeliveryController::class, 'destroy'])->name('deliveries.destroy');
});

// ─── Админ-панель ──────────────────────────────────
// Два middleware:
//   auth  — пользователь должен быть залогинен;
//   admin — App\Http\Middleware\CheckIfAdmin проверяет users.is_admin.
// При подтверждении заявки (updateStatus → confirmed) списываются
// остатки товаров во внешнем магазине.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Все заявки всех пользователей (с фильтром по статусу и поиском)
    Route::get('/deliveries', [AdminDeliveryController::class, 'index'])->name('deliveries.index');

    // Карточка заявки с товарами и данными клиента
    Route::get('/deliveries/{delivery}', [AdminDeliveryController::class, 'show'])->name('deliveries.show');

    // Смена статуса + комментарий администратора.
    // Переход в "confirmed" запускает списание остатков в магазине.
    Route::patch('/deliveries/{delivery}/status', [AdminDeliveryController::class, 'updateStatus'])->name('deliveries.status');

    // Мягкое удаление заявки
    Route::delete('/deliveries/{delivery}', [AdminDeliveryController::class, 'destroy'])->name('deliveries.destroy');
});
