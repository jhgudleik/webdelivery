@extends('layouts.main')

@section('title', 'Настройки профиля')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-lg-9">

            <h2 class="mb-4 fw-bold">
                <i class="fas fa-user-cog me-2 text-danger"></i>
                Настройки профиля
            </h2>

            {{-- Уведомления --}}
            @if (session('status') === 'profile-updated')
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    Профиль успешно обновлён.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>
                </div>
            @endif

            @if (session('status') === 'password-updated')
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    Пароль успешно изменён.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>
                </div>
            @endif

            @if (session('status') === 'avatar-deleted')
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle me-2"></i>
                    Аватар удалён.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>
                </div>
            @endif


            {{-- Общие ошибки --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="fas fa-exclamation-circle me-2"></i>

                    <strong>Проверьте данные:</strong>

                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>
            @endif


            {{-- Вкладки --}}
            <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link active"
                        id="general-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#general"
                        type="button"
                        role="tab"
                    >
                        <i class="fas fa-user me-1"></i>
                        Основное
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="password-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#password"
                        type="button"
                        role="tab"
                    >
                        <i class="fas fa-lock me-1"></i>
                        Пароль
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link text-danger"
                        id="danger-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#danger"
                        type="button"
                        role="tab"
                    >
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Удаление
                    </button>
                </li>

            </ul>


            <div class="tab-content">


                {{-- =====================================================
                     ОСНОВНОЕ
                ====================================================== --}}

                <div
                    class="tab-pane fade show active"
                    id="general"
                    role="tabpanel"
                >

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <form
                                method="POST"
                                action="{{ route('profile.update') }}"
                                enctype="multipart/form-data"
                            >

                                @csrf
                                @method('PATCH')


                                {{-- Аватар --}}
                                <div class="d-flex align-items-center gap-4 mb-4">

                                    <div
                                        class="avatar-preview"
                                        id="avatarPreview"
                                    >

                                        @if($user->avatar)

                                            <img
                                                src="{{ asset('storage/' . $user->avatar) }}"
                                                alt="avatar"
                                                id="avatarImg"
                                            >

                                        @else

                                            <span id="avatarLetter">
                                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                            </span>

                                        @endif

                                    </div>


                                    <div class="flex-grow-1">

                                        <label
                                            for="avatar"
                                            class="form-label fw-semibold"
                                        >
                                            Аватар
                                        </label>

                                        <input
                                            type="file"
                                            class="form-control @error('avatar') is-invalid @enderror"
                                            id="avatar"
                                            name="avatar"
                                            accept="image/jpeg,image/png,image/webp"
                                        >

                                        @error('avatar')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <small class="text-muted">
                                            JPG, PNG или WEBP. Максимум 2 МБ.
                                        </small>


                                        {{-- Удаление аватара --}}
                                        @if($user->avatar)

                                            <div class="mt-2">

                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteAvatarModal"
                                                >
                                                    <i class="fas fa-trash me-1"></i>
                                                    Удалить аватар
                                                </button>

                                            </div>

                                        @endif

                                    </div>

                                </div>


                                {{-- Имя --}}
                                <div class="mb-3">

                                    <label
                                        for="name"
                                        class="form-label fw-semibold"
                                    >
                                        Имя
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control @error('name') is-invalid @enderror"
                                        id="name"
                                        name="name"
                                        value="{{ old('name', $user->name) }}"
                                        required
                                    >

                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                {{-- Email --}}
                                <div class="mb-3">

                                    <label
                                        for="email"
                                        class="form-label fw-semibold"
                                    >
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        id="email"
                                        name="email"
                                        value="{{ old('email', $user->email) }}"
                                        required
                                    >

                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                <div class="d-flex justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn btn-primary px-4"
                                    >
                                        <i class="fas fa-save me-1"></i>
                                        Сохранить
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     ПАРОЛЬ
                ====================================================== --}}

                <div
                    class="tab-pane fade"
                    id="password"
                    role="tabpanel"
                >

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <form
                                method="POST"
                                action="{{ route('profile.password') }}"
                            >

                                @csrf
                                @method('PUT')


                                {{-- Текущий пароль --}}
                                <div class="mb-3">

                                    <label
                                        for="current_password"
                                        class="form-label fw-semibold"
                                    >
                                        Текущий пароль
                                    </label>

                                    <input
                                        type="password"
                                        class="form-control @error('current_password') is-invalid @enderror"
                                        id="current_password"
                                        name="current_password"
                                        required
                                    >

                                    @error('current_password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                {{-- Новый пароль --}}
                                <div class="mb-3">

                                    <label
                                        for="password"
                                        class="form-label fw-semibold"
                                    >
                                        Новый пароль
                                    </label>

                                    <input
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        id="password"
                                        name="password"
                                        required
                                    >

                                    @error('password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                {{-- Подтверждение --}}
                                <div class="mb-3">

                                    <label
                                        for="password_confirmation"
                                        class="form-label fw-semibold"
                                    >
                                        Повторите новый пароль
                                    </label>

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        required
                                    >

                                </div>


                                <div class="d-flex justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn btn-primary px-4"
                                    >
                                        <i class="fas fa-key me-1"></i>
                                        Изменить пароль
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                     УДАЛЕНИЕ
                ====================================================== --}}

                <div
                    class="tab-pane fade"
                    id="danger"
                    role="tabpanel"
                >

                    <div class="card border-danger border-2 shadow-sm">

                        <div class="card-body p-4">

                            <h5 class="text-danger fw-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Удаление аккаунта
                            </h5>

                            <p class="text-muted mb-4">
                                После удаления аккаунта все данные будут
                                безвозвратно утеряны.
                                Это действие нельзя отменить.
                            </p>

                            <button
                                type="button"
                                class="btn btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteAccountModal"
                            >
                                <i class="fas fa-trash-alt me-1"></i>
                                Удалить аккаунт
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>

</div>


{{-- =====================================================
     МОДАЛКА: УДАЛЕНИЕ АВАТАРА
====================================================== --}}

<div
    class="modal fade"
    id="deleteAvatarModal"
    tabindex="-1"
>
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Удалить аватар?
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <div class="modal-body">

                Аватар будет удалён.
                Вы сможете загрузить новый в любой момент.

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Отмена
                </button>

                <form
                    method="POST"
                    action="{{ route('profile.avatar.delete') }}"
                >

                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Удалить
                    </button>

                </form>

            </div>

        </div>

    </div>
</div>


{{-- =====================================================
     СТИЛИ
====================================================== --}}

<style>

    .nav-pills .nav-link {
        color: #555;
        font-weight: 500;
        border-radius: 10px;
        padding: 0.6rem 1.2rem;
        transition: all 0.25s ease;
    }

    .nav-pills .nav-link:hover {
        background: #f8f9fa;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(
            135deg,
            #1a1a2e 0%,
            #16213e 100%
        );

        color: #fff;

        box-shadow:
            0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .nav-pills .nav-link.text-danger.active {
        background: #dc3545;
    }

    .avatar-preview {
        width: 110px;
        height: 110px;

        border-radius: 50%;

        background: linear-gradient(
            135deg,
            #e94560,
            #c73652
        );

        color: #fff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 2.6rem;
        font-weight: 700;

        overflow: hidden;

        border: 4px solid #fff;

        box-shadow:
            0 6px 20px rgba(233, 69, 96, 0.3);

        flex-shrink: 0;

        transition: all 0.3s ease;
    }

    .avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .card {
        border-radius: 16px;
    }

    .form-control {
        border-radius: 10px;
        padding: 0.65rem 1rem;
        border: 1.5px solid #e5e7eb;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: #e94560;

        box-shadow:
            0 0 0 0.2rem rgba(233, 69, 96, 0.15);
    }

    .btn-primary {
        background: linear-gradient(
            135deg,
            #e94560,
            #c73652
        );

        border: none;
        border-radius: 10px;
        font-weight: 500;

        transition: all 0.25s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(
            135deg,
            #c73652,
            #a82a44
        );

        transform: translateY(-2px);

        box-shadow:
            0 6px 16px rgba(233, 69, 96, 0.35);
    }

</style>


{{-- =====================================================
     ПРЕВЬЮ АВАТАРА
====================================================== --}}

@push('scripts')

<script>

    document
        .getElementById('avatar')
        ?.addEventListener('change', function (e) {

            const file = e.target.files[0];

            if (!file) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (ev) {

                const preview =
                    document.getElementById('avatarPreview');

                preview.innerHTML =
                    '<img src="' +
                    ev.target.result +
                    '" alt="preview">';

            };

            reader.readAsDataURL(file);

        });

</script>

@endpush

@endsection
