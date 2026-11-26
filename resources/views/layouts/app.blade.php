<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel') · Gestión Social</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SweetAlert2 CDN Global --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-[#F5F7FB] text-[#1E293B] antialiased">

    {{-- Backdrop para móvil (cierra el menú al hacer click afuera) --}}
    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-40 bg-brand-950/60 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"
        aria-hidden="true"
    ></div>

    <div class="flex h-screen overflow-hidden">

        @include('layouts.partials.sidebar')

        {{-- Contenido --}}
        <div class="flex flex-1 flex-col overflow-hidden min-w-0">

            {{-- Barra superior: botón móvil, búsqueda global, periodo, notificaciones y perfil --}}
            <header class="flex h-[72px] shrink-0 items-center gap-3 sm:gap-6 border-b border-[#E2E8F0] bg-white px-4 sm:px-6 lg:px-8">

                {{-- Botón hamburguesa móvil --}}
                <button
                    type="button"
                    id="mobile-menu-btn"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-[#64748B] hover:bg-[#F1F5F9] hover:text-brand-950 focus:outline-none focus:ring-2 focus:ring-accent-500 lg:hidden"
                    aria-label="Abrir menú de navegación"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="ml-auto flex items-center gap-2 sm:gap-4 lg:gap-5 shrink-0">

                    @php
                        $usuarioNotificaciones = auth()->user();
                        $ultimasNotificaciones = $usuarioNotificaciones
                            ? $usuarioNotificaciones->notificaciones()->latest()->take(10)->get()
                            : collect();
                        $notificacionesNoLeidas = $usuarioNotificaciones
                            ? $usuarioNotificaciones->notificaciones()->noLeidas()->count()
                            : 0;
                    @endphp

                    <div class="relative">
                        <button
                            type="button"
                            id="notificaciones-btn"
                            class="relative text-[#64748B] hover:text-brand-950 p-1"
                            title="Notificaciones"
                            aria-label="Notificaciones"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="notificaciones-panel"
                        >
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            @if ($notificacionesNoLeidas > 0)
                                <span id="notificaciones-badge" class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full border-2 border-white bg-status-bad-fg px-1 text-[9px] font-bold leading-none text-white">
                                    {{ $notificacionesNoLeidas > 9 ? '9+' : $notificacionesNoLeidas }}
                                </span>
                            @endif
                        </button>

                        {{-- Panel de notificaciones (campana del top-bar) --}}
                        <div
                            id="notificaciones-panel"
                            class="absolute right-0 top-full z-50 mt-2 hidden w-80 overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xl"
                        >
                            <div class="flex items-center justify-between border-b border-[#E2E8F0] px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-[#64748B]">Notificaciones</p>
                                @if ($ultimasNotificaciones->isNotEmpty())
                                    <span class="rounded-full bg-brand-100 px-2 py-0.5 text-[10px] font-bold text-brand-950">{{ $ultimasNotificaciones->count() }}</span>
                                @endif
                            </div>

                            @if ($ultimasNotificaciones->isEmpty())
                                <p class="px-4 py-6 text-center text-xs text-[#94A3B8]">Sin notificaciones recientes.</p>
                            @else
                                <ul class="max-h-80 divide-y divide-[#F1F5F9] overflow-y-auto">
                                    @foreach ($ultimasNotificaciones as $notificacion)
                                        <li
                                            @class([
                                                'flex items-start gap-3 px-4 py-3',
                                                'bg-[#F8FAFC]' => $notificacion->leida_en === null,
                                            ])
                                            @if ($notificacion->leida_en === null) data-notificacion-no-leida @endif
                                        >
                                            <span @class([
                                                'mt-1 h-2 w-2 shrink-0 rounded-full',
                                                'bg-status-ok-fg' => $notificacion->tipo === 'success',
                                                'bg-status-bad-fg' => $notificacion->tipo === 'error',
                                                'bg-status-warn-fg' => $notificacion->tipo === 'warning',
                                                'bg-accent-500' => $notificacion->tipo === 'info',
                                            ])></span>
                                            <div class="min-w-0">
                                                <p class="break-words text-sm leading-snug text-brand-950">{{ $notificacion->mensaje }}</p>
                                                <p class="mt-0.5 text-[11px] text-[#94A3B8]">{{ $notificacion->created_at?->diffForHumans() ?? 'Ahora' }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    {{-- Menú de perfil (dropdown) --}}
                    <div class="relative">
                        <button
                            type="button"
                            id="perfil-btn"
                            class="flex items-center gap-2 text-[#64748B] hover:text-brand-950"
                            title="Mi perfil"
                            aria-label="Mi perfil"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="perfil-panel"
                        >
                            <span class="flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-accent-500 text-xs sm:text-sm font-semibold text-white">
                                {{ Str::of(auth()->user()->name ?? 'U')->substr(0, 1)->upper() }}
                            </span>
                            <svg class="hidden sm:block h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div
                            id="perfil-panel"
                            class="absolute right-0 top-full z-50 mt-2 hidden w-72 overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xl"
                        >
                            {{-- Cabecera con datos del usuario --}}
                            <div class="border-b border-[#E2E8F0] bg-[#F8FAFC] px-4 py-3">
                                <p class="truncate text-sm font-semibold text-brand-950">{{ auth()->user()->name }} {{ auth()->user()->apellido }}</p>
                                <p class="truncate text-xs text-[#64748B]">{{ auth()->user()->email }}</p>
                                <span @class([
                                    'mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold',
                                    'bg-brand-100 text-brand-950' => auth()->user()->hasRole('Admin'),
                                    'bg-emerald-50 text-emerald-800 border border-emerald-200' => ! auth()->user()->hasRole('Admin'),
                                ])>
                                    {{ auth()->user()->hasRole('Admin') ? 'Administrador' : 'Usuario' }}
                                </span>
                            </div>

                            <div class="p-1.5">
                                {{-- Configuración --}}
                                <a
                                    href="{{ route('configuracion') }}"
                                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-brand-950 transition-colors hover:bg-[#F1F5F9]"
                                >
                                    <svg class="h-4 w-4 text-[#64748B]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Configuración
                                </a>

                                {{-- Cerrar sesión --}}
                                <form method="POST" action="{{ route('logout') }}" class="m-0" data-confirmar="¿Deseas cerrar la sesión actual?">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-status-bad-fg transition-colors hover:bg-status-bad-bg/60">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15m-3 0l-3-3m0 0l3-3m-3 3H15" />
                                        </svg>
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Área de contenido con scroll propio --}}
            <main class="flex-1 overflow-y-auto px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <div class="mb-6">
                    <h1 class="text-2xl sm:text-3xl lg:text-[32px] font-bold uppercase leading-tight text-brand-950">@yield('page-title', 'Inicio')</h1>
                    @hasSection('page-subtitle')
                        <p class="mt-1 text-xs sm:text-sm text-[#64748B]">@yield('page-subtitle')</p>
                    @endif
                </div>

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Script de control de Sidebar Móvil --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('app-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');

        function openSidebar() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            backdrop.classList.remove('opacity-0', 'pointer-events-none');
            backdrop.classList.add('opacity-100', 'pointer-events-auto');
            document.body.classList.add('overflow-hidden');
        }

        function closeSidebar() {
            if (!sidebar || !backdrop) return;
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            backdrop.classList.add('opacity-0', 'pointer-events-none');
            backdrop.classList.remove('opacity-100', 'pointer-events-auto');
            document.body.classList.remove('overflow-hidden');
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth < 1024) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });

        // Panel de notificaciones (campana del top-bar)
        const notificacionesBtn = document.getElementById('notificaciones-btn');
        const notificacionesPanel = document.getElementById('notificaciones-panel');
        const notificacionesBadge = document.getElementById('notificaciones-badge');
        let notificacionesMarcadas = false;

        const marcarNotificacionesLeidas = () => {
            if (notificacionesMarcadas || !notificacionesBadge) return;

            notificacionesMarcadas = true;
            notificacionesBadge.remove();

            notificacionesPanel?.querySelectorAll('[data-notificacion-no-leida]').forEach((item) => {
                item.classList.remove('bg-[#F8FAFC]');
                item.removeAttribute('data-notificacion-no-leida');
            });

            fetch(@json(route('notificaciones.leer')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    'Accept': 'application/json',
                },
            }).catch(() => {});
        };

        if (notificacionesBtn && notificacionesPanel) {
            notificacionesBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const abierto = !notificacionesPanel.classList.contains('hidden');
                notificacionesPanel.classList.toggle('hidden', abierto);
                notificacionesBtn.setAttribute('aria-expanded', String(!abierto));
                if (!abierto) marcarNotificacionesLeidas();
            });

            document.addEventListener('click', (e) => {
                if (notificacionesPanel.classList.contains('hidden')) return;
                if (notificacionesPanel.contains(e.target) || notificacionesBtn.contains(e.target)) return;
                notificacionesPanel.classList.add('hidden');
                notificacionesBtn.setAttribute('aria-expanded', 'false');
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !notificacionesPanel.classList.contains('hidden')) {
                    notificacionesPanel.classList.add('hidden');
                    notificacionesBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Menú de perfil (top-bar)
        const perfilBtn = document.getElementById('perfil-btn');
        const perfilPanel = document.getElementById('perfil-panel');

        if (perfilBtn && perfilPanel) {
            perfilBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const abierto = !perfilPanel.classList.contains('hidden');
                perfilPanel.classList.toggle('hidden', abierto);
                perfilBtn.setAttribute('aria-expanded', String(!abierto));
            });

            document.addEventListener('click', (e) => {
                if (perfilPanel.classList.contains('hidden')) return;
                if (perfilPanel.contains(e.target) || perfilBtn.contains(e.target)) return;
                perfilPanel.classList.add('hidden');
                perfilBtn.setAttribute('aria-expanded', 'false');
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !perfilPanel.classList.contains('hidden')) {
                    perfilPanel.classList.add('hidden');
                    perfilBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    </script>

    {{-- Sistema Global de SweetAlert2: Confirmación de Eliminación, Alertas Flash y Manejo de Errores 403 / 404 --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Notificaciones Flash de Sesión
        @if (session('status'))
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                text: @json(session('status')),
                confirmButtonColor: '#0f1424',
                confirmButtonText: 'Entendido'
            });
        @endif

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                text: @json(session('success')),
                confirmButtonColor: '#0f1424',
                confirmButtonText: 'Entendido'
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error de Acceso o Validación',
                text: @json(session('error')),
                confirmButtonColor: '#0f1424',
                confirmButtonText: 'Entendido'
            });
        @endif

        @if (session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: @json(session('warning')),
                confirmButtonColor: '#0f1424',
                confirmButtonText: 'Entendido'
            });
        @endif

        // 2. Interceptor Global para Confirmar Eliminación con SweetAlert2
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            const methodInput = form.querySelector('input[name="_method"]');
            const isDelete = (methodInput && methodInput.value.toUpperCase() === 'DELETE')
                || form.classList.contains('form-eliminar')
                || form.hasAttribute('data-confirm-delete');

            if (isDelete) {
                if (form.dataset.swalConfirmed === 'true') {
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();

                const title = form.getAttribute('data-swal-title') || '¿Confirmas que deseas eliminar este registro?';
                const text = form.getAttribute('data-swal-text') || 'Esta acción no se puede deshacer y los datos asociados se eliminarán permanentemente.';
                const confirmBtn = form.getAttribute('data-swal-confirm') || 'Sí, eliminar';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#E11D2E',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: confirmBtn,
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.swalConfirmed = 'true';
                        form.submit();
                    }
                });
            }
        }, true);

        // 3. Interceptor Global para formularios con confirmación genérica (data-confirmar)
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            const mensaje = form.getAttribute('data-confirmar');
            if (mensaje) {
                if (form.dataset.swalConfirmed === 'true') {
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();

                Swal.fire({
                    title: '¿Confirmar acción?',
                    text: mensaje,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0f1424',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.swalConfirmed = 'true';
                        form.submit();
                    }
                });
            }
        }, true);

        // 4. Interceptor Global de Fetch para Respuestas 403 y 404
        if (window.fetch) {
            const originalFetch = window.fetch;
            window.fetch = async function (...args) {
                try {
                    const response = await originalFetch.apply(this, args);
                    if (response.status === 403) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Acceso Denegado (403)',
                            text: 'No dispones de los permisos necesarios para realizar esta acción.',
                            confirmButtonColor: '#0f1424',
                            confirmButtonText: 'Entendido'
                        });
                    } else if (response.status === 404) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Recurso No Encontrado (404)',
                            text: 'El recurso solicitado no fue encontrado en el servidor.',
                            confirmButtonColor: '#0f1424',
                            confirmButtonText: 'Entendido'
                        });
                    }
                    return response;
                } catch (err) {
                    throw err;
                }
            };
        }
    });
    </script>

    @stack('scripts')
</body>
</html>
