<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Сервис доставки')</title>
    <link rel="icon" type="image/png" href="/favicon.png">

    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Базовые стили и CSS-переменные через нативный CSS */
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1; /* растягивается между хедером и футером */
        }
        /* Кастомная навигация: тёмный градиент + акцент #e94560 */
        .navbar-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .navbar-custom .navbar-brand {
            font-weight: 700;
            color: #fff;
            font-size: 1.5rem;
        }
        .navbar-custom .navbar-brand:hover {
            color: #e94560;
        }
        .navbar-custom .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .navbar-custom .nav-link:hover {
            color: #e94560 !important;
            transform: translateY(-2px);
        }
        .navbar-custom .btn-outline-light {
            border-color: rgba(255,255,255,0.3);
            color: #fff;
            transition: all 0.3s ease;
        }
        .navbar-custom .btn-outline-light:hover {
            background: #e94560;
            border-color: #e94560;
            transform: translateY(-2px);
        }
        .navbar-custom .btn-primary {
            background: #e94560;
            border-color: #e94560;
            transition: all 0.3s ease;
        }
        .navbar-custom .btn-primary:hover {
            background: #c73652;
            border-color: #c73652;
            transform: translateY(-2px);
        }

        /* Аватар пользователя в навбаре и дропдауне */
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #ffffff;
            color: #fff;
        }
        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .avatar-circle.avatar-lg {
            width: 60px;
            height: 60px;
        }

        /* Кастомные дропдауны */
        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border: none;
            padding: 0.5rem 0;
        }
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background: #f8f9fa;
            color: #e94560;
        }
        .dropdown-item i {
            width: 20px;
            margin-right: 8px;
            color: #e94560;
        }

        /* Футер: тот же градиент, что и хедер */
        .footer-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: rgba(255,255,255,0.8);
            padding: 3rem 0 1.5rem;
            margin-top: auto; /* прижимается к низу */
        }
        .footer-custom h5 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 1.2rem;
        }
        .footer-custom a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .footer-custom a:hover {
            color: #e94560;
            transform: translateX(5px);
        }
        .footer-custom .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        .footer-custom .social-icons a:hover {
            background: #e94560;
            transform: translateY(-5px);
        }
        .footer-custom .border-top {
            border-color: rgba(255,255,255,0.1) !important;
        }

        /* Карточки: скруглённые, с тенью, ховер-эффект */
        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: none;
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        /* Кнопки: градиент, скругление, ховер */
        .btn-primary {
            background: linear-gradient(135deg, #e94560, #c73652);
            border: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.25s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c73652, #a82a44);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(233, 69, 96, 0.35);
        }

        /* Формы: скруглённые, с фокусом по акценту */
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.65rem 1rem;
            border: 1.5px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #e94560;
            box-shadow: 0 0 0 0.2rem rgba(233, 69, 96, 0.15);
        }
    </style>
    @stack('styles')
</head>
<body>
<!-- ШАПКА -->
<header>
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <!-- Логотип -->
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-truck-fast me-2"></i>WebDelivery
            </a>

            <!-- Кнопка для мобильных -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Основное меню -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}"><i class="fas fa-home me-1"></i>Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('catalog.index') }}"><i class="fas fa-boxes-stacked me-1"></i>Каталог</a>
                    </li>
                    @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('deliveries.index') }}"><i class="fas fa-truck me-1"></i>Мои доставки</a>
                    </li>
                    @endauth
                    @auth
                        @if(auth()->user()->is_admin)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.deliveries.index') }}"><i class="fas fa-shield-halved me-1"></i>Админ</a>
                        </li>
                        @endif
                    @endauth
                </ul>

                @auth
                    <!-- Dropdown профиля -->
                    <div class="dropdown profile-dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                           href="#"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <div class="avatar-circle">
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar">
                                @else
                                    <span style="color: #1a1a2e; font-weight: 700;">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <span class="d-none d-lg-inline text-white fw-500">
                                {{ auth()->user()->name }}
                            </span>
                            <i class="fas fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end profile-menu">
                            <li class="profile-header">
                                <div class="d-flex align-items-center gap-3 p-3">
                                    <div class="avatar-circle avatar-lg">
                                        @if(auth()->user()->avatar)
                                            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="avatar">
                                        @else
                                            <span style="color: #1a1a2e; font-weight: 700;">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-truncate">{{ auth()->user()->name }}</div>
                                        <small class="text-muted text-truncate d-block">{{ auth()->user()->email }}</small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile') }}">
                                    <i class="fas fa-user-circle"></i>
                                    <span>Мой профиль</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('deliveries.index') }}">
                                    <i class="fas fa-truck"></i>
                                    <span>Мои доставки</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Выйти из аккаунта</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <div class="d-flex gap-2">
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm px-3">
                            <i class="fas fa-sign-in-alt me-1"></i>Вход
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm px-3">
                            <i class="fas fa-user-plus me-1"></i>Регистрация
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>
</header>

<!-- ОСНОВНОЙ КОНТЕНТ -->
<main class="main-content">
    <div class="container py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{!! session('warning') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @yield('content')
    </div>
</main>

<!-- ФУТЕР -->
<footer class="footer-custom">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h5><i class="fas fa-truck-fast me-2"></i>WebDelivery</h5>
                <p class="text-white-50" style="font-size: 0.95rem;">
                    Сервис доставки товаров из интернет-магазинов.
                    Быстро, надёжно, удобно.
                </p>
                <div class="social-icons mt-3">
                    <a href="#"><i class="fab fa-vk"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5><i class="fas fa-link me-2"></i>Навигация</h5>
                <a href="{{ route('home') }}"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>Главная</a><br>
                <a href="{{ route('catalog.index') }}"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>Каталог</a><br>
                @auth
                    <a href="{{ route('deliveries.index') }}"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>Мои доставки</a><br>
                    <a href="{{ route('profile') }}"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>Профиль</a>
                @endauth
            </div>

            <div class="col-lg-3 col-md-6">
                <h5><i class="fas fa-info-circle me-2"></i>Информация</h5>
                <a href="#"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>О сервисе</a><br>
                <a href="#"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>Доставка и оплата</a><br>
                <a href="#"><i class="fas fa-chevron-right me-1" style="font-size: 10px;"></i>FAQ</a>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5><i class="fas fa-address-card me-2"></i>Контакты</h5>
                <p class="text-white-50 mb-1">
                    <i class="fas fa-map-marker-alt me-2" style="color: #e94560;"></i>
                    г. Москва, ул. Примерная, д. 1
                </p>
                <p class="text-white-50 mb-1">
                    <i class="fas fa-phone me-2" style="color: #e94560;"></i>
                    +7 (999) 123-45-67
                </p>
                <p class="text-white-50 mb-3">
                    <i class="fas fa-envelope me-2" style="color: #e94560;"></i>
                    info@webdelivery.ru
                </p>
            </div>
        </div>

        <div class="border-top pt-3 mt-4 text-center text-white-50">
            <p class="mb-0" style="font-size: 0.9rem;">
                &copy; {{ date('Y') }} WebDelivery. Все права защищены.
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle (Popper + Bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Скрипты из вьюх (через @push('scripts') в create/edit/profile) -->
@stack('scripts')
</body>
</html>