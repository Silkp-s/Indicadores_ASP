<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · Sistema APS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-full bg-[#0F1424] text-slate-100 antialiased flex flex-col justify-center items-center px-4 py-12 relative overflow-hidden selection:bg-accent-500 selection:text-white">

    {{-- Efectos de fondo sutiles --}}
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-accent-600/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-rose-600/10 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-xl mx-auto text-center">

        {{-- Tarjeta Principal del Error --}}
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-8 sm:p-12 shadow-2xl backdrop-blur-md">
            
            {{-- Icono y Código de Estado --}}
            <div class="flex flex-col items-center justify-center gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl @yield('badge_bg', 'bg-rose-500/10 text-rose-400') ring-1 @yield('badge_ring', 'ring-rose-500/20') shadow-lg shadow-rose-950/20">
                    @yield('icon')
                </div>
                
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-mono font-bold uppercase tracking-wider @yield('badge_color', 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30')">
                    Código HTTP @yield('code')
                </span>
            </div>

            {{-- Textos del Error --}}
            <h1 class="mt-6 text-2xl sm:text-3xl font-extrabold uppercase tracking-tight text-white">
                @yield('title')
            </h1>
            
            <p class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed max-w-md mx-auto">
                @yield('message')
            </p>

            {{-- Botones de Navegación --}}
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    type="button"
                    onclick="window.history.length > 1 ? window.history.back() : window.location.href = '{{ url('/') }}'"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/10 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/15 transition-all shadow-xs"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Regresar
                </button>

                <a
                    href="{{ route('centro-mando') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-accent-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-accent-600/30 hover:bg-accent-500 transition-all"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Centro de Mando
                </a>
            </div>

            <div class="mt-8 pt-6 border-t border-white/10 text-xs text-slate-400">
                Plataforma de Gestión Social y Atención Primaria de Salud (APS)
            </div>
        </div>
    </div>

    {{-- Disparador Automático de SweetAlert2 al Cargar la Vista de Error --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: '@yield("swal_icon", "error")',
                    title: '@yield("swal_title", "Error")',
                    text: '@yield("swal_text", "Ha ocurrido un error inesperado.")',
                    confirmButtonColor: '#0f1424',
                    confirmButtonText: 'Entendido',
                    showCancelButton: true,
                    cancelButtonText: 'Ir al Centro de Mando',
                    cancelButtonColor: '#2f6fed',
                    reverseButtons: true,
                    backdrop: `rgba(15, 20, 36, 0.75)`
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = "{{ route('centro-mando') }}";
                    }
                });
            }
        });
    </script>
</body>
</html>
