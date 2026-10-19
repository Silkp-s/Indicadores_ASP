@extends('layouts.app')

@section('title', 'Panel de Administración')
@section('page-title', 'Panel de Administración')
@section('page-subtitle', 'Gestión centralizada de usuarios, metas sanitarias, IAAPS y mapeo de celdas REM')

@section('content')
<div class="space-y-6">

    {{-- Notificaciones flash de éxito o error --}}
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-status-ok-fg/30 bg-status-ok-bg p-4 text-sm font-medium text-status-ok-fg shadow-xs">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-status-bad-fg/30 bg-status-bad-bg p-4 text-sm text-status-bad-fg shadow-xs">
            <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-bold">Por favor corrige los siguientes inconvenientes:</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Navegación por Secciones (Tabs principales del panel) --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-[#E2E8F0] pb-3">
        {{-- Pestaña 1: Usuarios --}}
        <a
            href="{{ route('admin.dashboard', ['tab' => 'usuarios']) }}"
            @class([
                'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all',
                'bg-brand-950 text-white shadow-xs' => $tab === 'usuarios',
                'bg-white text-[#64748B] hover:text-brand-950 hover:bg-[#F1F5F9] border border-[#E2E8F0]' => $tab !== 'usuarios',
            ])
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            </svg>
            <span>Sección 1: Usuarios</span>
            <span @class([
                'ml-1 rounded-full px-2 py-0.5 text-xs font-bold',
                'bg-white/20 text-white' => $tab === 'usuarios',
                'bg-[#F1F5F9] text-brand-950' => $tab !== 'usuarios',
            ])>
                {{ $stats['total_usuarios'] }}
            </span>
        </a>

        {{-- Pestaña 2: Metas Sanitarias e IAAPS --}}
        <a
            href="{{ route('admin.dashboard', ['tab' => 'iaaps']) }}"
            @class([
                'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all',
                'bg-brand-950 text-white shadow-xs' => $tab === 'iaaps',
                'bg-white text-[#64748B] hover:text-brand-950 hover:bg-[#F1F5F9] border border-[#E2E8F0]' => $tab !== 'iaaps',
            ])
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Sección 2: Metas Sanitarias e IAAPS</span>
            <span @class([
                'ml-1 rounded-full px-2 py-0.5 text-xs font-bold',
                'bg-white/20 text-white' => $tab === 'iaaps',
                'bg-[#F1F5F9] text-brand-950' => $tab !== 'iaaps',
            ])>
                {{ $stats['total_indicadores'] }}
            </span>
        </a>

        {{-- Pestaña 3: Mapeo de Celdas REM --}}
        <a
            href="{{ route('admin.dashboard', ['tab' => 'rem']) }}"
            @class([
                'flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all',
                'bg-brand-950 text-white shadow-xs' => $tab === 'rem',
                'bg-white text-[#64748B] hover:text-brand-950 hover:bg-[#F1F5F9] border border-[#E2E8F0]' => $tab !== 'rem',
            ])
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
            </svg>
            <span>Sección 3: Mapeo de Celdas REM</span>
            <span @class([
                'ml-1 rounded-full px-2 py-0.5 text-xs font-bold',
                'bg-white/20 text-white' => $tab === 'rem',
                'bg-[#F1F5F9] text-brand-950' => $tab !== 'rem',
            ])>
                {{ $stats['total_mapeos_rem'] }}
            </span>
        </a>
    </div>

    {{-- ========================================================================= --}}
    {{-- SECCIÓN 1: GESTIÓN DE USUARIOS                                            --}}
    {{-- ========================================================================= --}}
    @if ($tab === 'usuarios')
        <div class="space-y-6">

            {{-- Estadísticas rápidas de usuarios --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Total usuarios</p>
                    <p class="mt-2 text-2xl font-bold text-brand-950">{{ $stats['total_usuarios'] }}</p>
                    <p class="mt-1 text-xs text-[#94A3B8]">Cuentas registradas en la red</p>
                </div>
                <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Administradores</p>
                    <p class="mt-2 text-2xl font-bold text-brand-950">{{ $stats['total_admins'] }}</p>
                    <p class="mt-1 text-xs text-[#94A3B8]">Acceso total de administración</p>
                </div>
                <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Usuarios Regulares</p>
                    <p class="mt-2 text-2xl font-bold text-brand-950">{{ \App\Models\User::role('Usuario')->count() }}</p>
                    <p class="mt-1 text-xs text-[#94A3B8]">Acceso operativo y de consulta</p>
                </div>
                <div class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-xs">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#64748B]">Registrados Hoy</p>
                    <p class="mt-2 text-2xl font-bold text-brand-950">{{ \App\Models\User::whereDate('created_at', today())->count() }}</p>
                    <p class="mt-1 text-xs text-[#94A3B8]">Altas del día actual</p>
                </div>
            </div>

            {{-- Barra de búsqueda y botón nuevo usuario --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-1 flex-wrap items-center gap-3">
                    <input type="hidden" name="tab" value="usuarios">
                    <div class="relative w-full flex-1 sm:min-w-[260px]">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            name="q_users"
                            value="{{ $qUsers }}"
                            placeholder="Buscar por nombre, apellido o correo..."
                            class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        >
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                    >
                        Buscar
                    </button>
                    @if ($qUsers)
                        <a
                            href="{{ route('admin.dashboard', ['tab' => 'usuarios']) }}"
                            class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </form>

                <div>
                    <a
                        href="{{ route('admin.users.create') }}"
                        class="flex items-center gap-2 rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Nuevo usuario</span>
                    </a>
                </div>
            </div>

            {{-- Tabla de Usuarios --}}
            <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B] border-b border-[#E2E8F0]">
                            <tr>
                                <th class="px-5 py-3.5">Usuario</th>
                                <th class="px-5 py-3.5">Email</th>
                                <th class="px-5 py-3.5">Roles</th>
                                <th class="px-5 py-3.5">Fecha Registro</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            @forelse ($users as $user)
                                <tr class="hover:bg-[#F8FAFC]/80 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-950 text-xs">
                                                {{ Str::upper(Str::substr($user->name, 0, 1)) }}{{ Str::upper(Str::substr($user->apellido, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-brand-950">{{ $user->name }} {{ $user->apellido }}</p>
                                                @if ($user->id === auth()->id())
                                                    <span class="inline-block text-[10px] text-accent-600 font-semibold">(Tú)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#475569] font-mono text-xs">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($user->roles as $role)
                                                <span @class([
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                                    'bg-brand-100 text-brand-950' => $role->name === 'Admin',
                                                    'bg-emerald-50 text-emerald-800 border border-emerald-200' => $role->name === 'Usuario',
                                                    'bg-slate-100 text-slate-700' => !in_array($role->name, ['Admin', 'Usuario']),
                                                ])>
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-[#64748B] text-xs">
                                        {{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('admin.users.edit', $user) }}"
                                                class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-1.5 text-xs font-semibold text-[#475569] hover:bg-[#F8FAFC] hover:text-accent-600 transition-colors"
                                                title="Editar datos y roles"
                                            >
                                                Editar
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.users.destroy', $user) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('¿Confirmas que deseas eliminar permanentemente a “{{ $user->name }} {{ $user->apellido }}”?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-status-bad-fg/30 bg-status-bad-bg px-2.5 py-1.5 text-xs font-semibold text-status-bad-fg hover:bg-status-bad-fg/15 transition-colors"
                                                        title="Eliminar usuario"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-[#64748B]">
                                        No se encontraron usuarios coincidentes.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación de usuarios --}}
                @if ($users->hasPages() || $users->total() > 0)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-6 py-3.5 text-sm text-[#64748B]">
                        <span>Mostrando <strong class="text-brand-950">{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}</strong> de {{ $users->total() }} usuarios</span>
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- SECCIÓN 2: METAS SANITARIAS E IAAPS (CRUD ADMINISTRATIVO)                 --}}
    {{-- ========================================================================= --}}
    @if ($tab === 'iaaps')
        <div class="space-y-6">

            {{-- Banner informativo --}}
            <div class="rounded-xl border border-brand-800/20 bg-brand-50/50 p-4 flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-brand-950">Mantenedor Administrativo de Metas Sanitarias e IAAPS</h3>
                    <p class="text-xs text-[#64748B] mt-0.5">
                        Crea, actualiza, edita o elimina los indicadores clave comunales. También puedes activar o desactivar indicadores de forma inmediata.
                    </p>
                </div>
                <button
                    type="button"
                    data-abrir-nuevo-indicador
                    class="shrink-0 flex items-center gap-2 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Nueva Meta / IAAPS</span>
                </button>
            </div>

            {{-- Filtros y búsqueda --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-1 flex-wrap items-center gap-3">
                    <input type="hidden" name="tab" value="iaaps">

                    {{-- Búsqueda --}}
                    <div class="relative w-full flex-1 sm:min-w-[240px]">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            name="q_iaaps"
                            value="{{ $qIaaps }}"
                            placeholder="Buscar por código o nombre..."
                            class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        >
                    </div>

                    {{-- Filtro Tipo --}}
                    <div class="w-full sm:w-auto min-w-[160px]">
                        <select
                            name="tipo_iaaps"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        >
                            <option value="todas" @selected($tipoIaaps === 'todas')>Ver: IAAPS y Metas</option>
                            <option value="Meta Sanitaria" @selected($tipoIaaps === 'Meta Sanitaria')>Solo Metas Sanitarias</option>
                            <option value="IAAPS" @selected($tipoIaaps === 'IAAPS')>Solo IAAPS</option>
                        </select>
                    </div>

                    {{-- Filtro Estado --}}
                    <div class="w-full sm:w-auto min-w-[140px]">
                        <select
                            name="estado_iaaps"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-brand-950 focus:border-accent-500 focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        >
                            <option value="todos" @selected($estadoIaaps === 'todos')>Estado: Todos</option>
                            <option value="activo" @selected($estadoIaaps === 'activo')>Activos</option>
                            <option value="inactivo" @selected($estadoIaaps === 'inactivo')>Inactivos</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                    >
                        Filtrar
                    </button>
                    @if ($qIaaps || $tipoIaaps !== 'todas' || $estadoIaaps !== 'todos')
                        <a
                            href="{{ route('admin.dashboard', ['tab' => 'iaaps']) }}"
                            class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </form>
            </div>

            {{-- Tabla de Metas e IAAPS --}}
            <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B] border-b border-[#E2E8F0]">
                            <tr>
                                <th class="px-5 py-3.5">Código</th>
                                <th class="px-5 py-3.5">Nombre del Indicador</th>
                                <th class="px-5 py-3.5">Tipo</th>
                                <th class="px-5 py-3.5 text-right">Meta (%)</th>
                                <th class="px-5 py-3.5">Periodicidad / Fuente</th>
                                <th class="px-5 py-3.5 text-center">Estado</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]" id="iaaps-admin-filas">
                            @forelse ($indicadores as $ind)
                                @php
                                    $indJson = json_encode([
                                        'id' => $ind->id,
                                        'codigo' => $ind->codigo,
                                        'nombre' => $ind->nombre,
                                        'tipo' => $ind->tipo,
                                        'target_value' => (float) $ind->target_value,
                                        'periodicidad' => $ind->periodicidad,
                                        'fu' => $ind->fu,
                                        'numerador_description' => $ind->numerador_description,
                                        'denominador_description' => $ind->denominador_description,
                                        'is_active' => (bool) $ind->is_active,
                                        'url_actualizar' => route('admin.indicadores.update', $ind),
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                @endphp
                                <tr class="hover:bg-[#F8FAFC]/80 transition-colors" data-indicador="{{ $indJson }}">
                                    <td class="px-5 py-3.5 font-bold font-mono text-brand-950 whitespace-nowrap">
                                        <span class="bg-[#F1F5F9] px-2 py-0.5 rounded text-xs border border-[#CBD5E1]">
                                            {{ $ind->codigo }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-semibold text-brand-950">{{ $ind->nombre }}</div>
                                        @if ($ind->numerador_description || $ind->denominador_description)
                                            <div class="text-[11px] text-[#64748B] line-clamp-1 mt-0.5">
                                                N: {{ $ind->numerador_description ?: '—' }} | D: {{ $ind->denominador_description ?: '—' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if ($ind->tipo === 'Meta Sanitaria')
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                                Meta Sanitaria
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200">
                                                IAAPS
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-mono font-bold text-brand-950 whitespace-nowrap">
                                        {{ $ind->meta_formateada }}%
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-[#64748B] whitespace-nowrap">
                                        <span class="font-medium text-brand-950">{{ $ind->periodicidad }}</span>
                                        <span class="text-[#94A3B8]">·</span>
                                        <span>{{ $ind->fu }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        @if ($ind->is_active)
                                            <span class="inline-flex rounded-full bg-status-ok-bg px-2.5 py-0.5 text-xs font-semibold text-status-ok-fg">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-[#F1F5F9] px-2.5 py-0.5 text-xs font-semibold text-[#64748B]">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Botón Editar --}}
                                            <button
                                                type="button"
                                                data-accion="editar-indicador"
                                                class="rounded-lg border border-[#CBD5E1] bg-white px-2.5 py-1 text-xs font-semibold text-[#475569] hover:bg-[#F8FAFC] hover:text-accent-600 transition-colors"
                                                title="Editar indicador"
                                            >
                                                Editar
                                            </button>

                                            {{-- Cambiar Estado --}}
                                            <form method="POST" action="{{ route('admin.indicadores.estado', $ind) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $ind->is_active ? 0 : 1 }}">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-[#CBD5E1] bg-white px-2 py-1 text-xs font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                                                    title="{{ $ind->is_active ? 'Desactivar' : 'Activar' }}"
                                                >
                                                    {{ $ind->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>

                                            {{-- Eliminar --}}
                                            <form
                                                method="POST"
                                                action="{{ route('admin.indicadores.destroy', $ind) }}"
                                                class="inline"
                                                onsubmit="return confirm('¿Confirmas que deseas eliminar el indicador “{{ $ind->codigo }} · {{ $ind->nombre }}”?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-status-bad-fg/30 bg-status-bad-bg px-2.5 py-1 text-xs font-semibold text-status-bad-fg hover:bg-status-bad-fg/15 transition-colors"
                                                    title="Eliminar"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-sm text-[#64748B]">
                                        No se encontraron metas sanitarias ni indicadores IAAPS.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación IAAPS --}}
                @if ($indicadores->hasPages() || $indicadores->total() > 0)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-6 py-3.5 text-sm text-[#64748B]">
                        <span>Mostrando <strong class="text-brand-950">{{ $indicadores->firstItem() ?? 0 }}–{{ $indicadores->lastItem() ?? 0 }}</strong> de {{ $indicadores->total() }} indicadores</span>
                        {{ $indicadores->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- SECCIÓN 3: CONFIGURACIÓN DE MAPEO DE CELDAS REM                           --}}
    {{-- ========================================================================= --}}
    @if ($tab === 'rem')
        <div class="space-y-6">

            {{-- Banner explicativo del módulo de mapeo REM --}}
            <div class="rounded-xl border border-accent-500/20 bg-accent-50/40 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-lg bg-accent-500/10 p-1.5 text-accent-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </span>
                        <h3 class="text-base font-bold text-brand-950">Configurador de Celdas REM para Extracción Automática</h3>
                    </div>
                    <p class="text-xs text-[#64748B] max-w-3xl leading-relaxed">
                        Indica al motor de importación en qué hoja de la planilla REM y en qué coordenada(s) exacta(s) se encuentra el <strong>Numerador (N)</strong> y el <strong>Denominador (D)</strong>. Las modificaciones se aplican en tiempo real en los cálculos de importación.
                    </p>
                </div>

                <button
                    type="button"
                    data-abrir-nuevo-rem
                    class="shrink-0 flex items-center justify-center gap-2 rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Nuevo Mapeo de Celda</span>
                </button>
            </div>

            {{-- Buscador de Mapeos REM --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-xs">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-1 flex-wrap items-center gap-3">
                    <input type="hidden" name="tab" value="rem">
                    <div class="relative w-full flex-1 sm:min-w-[260px]">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            name="q_rem"
                            value="{{ $qRem }}"
                            placeholder="Buscar por hoja, celda, código o descripción..."
                            class="w-full rounded-lg border border-[#CBD5E1] bg-[#F8FAFC] py-2 pl-10 pr-3 text-sm text-brand-950 placeholder:text-[#94A3B8] focus:border-accent-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-500/20"
                        >
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors"
                    >
                        Buscar
                    </button>
                    @if ($qRem)
                        <a
                            href="{{ route('admin.dashboard', ['tab' => 'rem']) }}"
                            class="rounded-lg border border-[#CBD5E1] bg-white px-3 py-2 text-sm font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </form>
            </div>

            {{-- Tabla de Mapeos REM --}}
            <div class="overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-xs font-semibold uppercase tracking-wider text-[#64748B] border-b border-[#E2E8F0]">
                            <tr>
                                <th class="px-5 py-3.5">Indicador</th>
                                <th class="px-5 py-3.5">Tipo</th>
                                <th class="px-5 py-3.5">Hoja REM</th>
                                <th class="px-5 py-3.5 font-mono">Celda Numerador</th>
                                <th class="px-5 py-3.5 font-mono">Celda Denominador</th>
                                <th class="px-5 py-3.5">Sección / Notas</th>
                                <th class="px-5 py-3.5 text-center">Estado</th>
                                <th class="px-5 py-3.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]" id="rem-admin-filas">
                            @forelse ($remMappings as $map)
                                @php
                                    $mapJson = json_encode([
                                        'id' => $map->id,
                                        'indicadores_id' => $map->indicadores_id,
                                        'hoja' => $map->hoja,
                                        'celda_numerador' => $map->celda_numerador,
                                        'celda_denominador' => $map->celda_denominador,
                                        'seccion' => $map->seccion,
                                        'descripcion' => $map->descripcion,
                                        'is_active' => (bool) $map->is_active,
                                        'url_actualizar' => route('admin.rem-mappings.update', $map),
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                @endphp
                                <tr class="hover:bg-[#F8FAFC]/80 transition-colors" data-mapping="{{ $mapJson }}">
                                    {{-- Indicador --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block font-mono font-bold text-xs bg-[#F1F5F9] px-2 py-0.5 rounded border border-[#CBD5E1] text-brand-950">
                                                {{ $map->indicador?->codigo ?? 'N/A' }}
                                            </span>
                                            <span class="font-medium text-brand-950 line-clamp-1">
                                                {{ $map->indicador?->nombre ?? 'Indicador eliminado' }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- Tipo --}}
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        @if ($map->indicador?->tipo === 'Meta Sanitaria')
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                                Meta Sanitaria
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200">
                                                IAAPS
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Hoja REM --}}
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="inline-flex rounded-md bg-brand-950 px-2.5 py-1 text-xs font-bold text-white font-mono">
                                            REM-{{ $map->hoja }}
                                        </span>
                                    </td>

                                    {{-- Celda Numerador --}}
                                    <td class="px-5 py-3.5 font-mono text-xs font-bold text-blue-800 whitespace-nowrap">
                                        <span class="bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                            {{ $map->celda_numerador }}
                                        </span>
                                    </td>

                                    {{-- Celda Denominador --}}
                                    <td class="px-5 py-3.5 font-mono text-xs whitespace-nowrap">
                                        @if ($map->celda_denominador)
                                            <span class="bg-purple-50 text-purple-800 px-2 py-0.5 rounded border border-purple-200 font-bold">
                                                {{ $map->celda_denominador }}
                                            </span>
                                        @else
                                            <span class="text-[#94A3B8] italic">Población / Meta</span>
                                        @endif
                                    </td>

                                    {{-- Sección / Descripción --}}
                                    <td class="px-5 py-3.5 text-xs text-[#64748B]">
                                        @if ($map->seccion)
                                            <span class="font-semibold text-brand-950 block">{{ $map->seccion }}</span>
                                        @endif
                                        <span class="line-clamp-1">{{ $map->descripcion ?: '—' }}</span>
                                    </td>

                                    {{-- Estado --}}
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        @if ($map->is_active)
                                            <span class="inline-flex rounded-full bg-status-ok-bg px-2.5 py-0.5 text-xs font-semibold text-status-ok-fg">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-[#F1F5F9] px-2.5 py-0.5 text-xs font-semibold text-[#64748B]">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Editar --}}
                                            <button
                                                type="button"
                                                data-accion="editar-rem"
                                                class="rounded-lg border border-[#CBD5E1] bg-white px-2.5 py-1 text-xs font-semibold text-[#475569] hover:bg-[#F8FAFC] hover:text-accent-600 transition-colors"
                                                title="Modificar celdas de extracción"
                                            >
                                                Editar
                                            </button>

                                            {{-- Cambiar estado --}}
                                            <form method="POST" action="{{ route('admin.rem-mappings.estado', $map) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $map->is_active ? 0 : 1 }}">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-[#CBD5E1] bg-white px-2 py-1 text-xs font-medium text-[#64748B] hover:bg-[#F8FAFC] transition-colors"
                                                    title="{{ $map->is_active ? 'Desactivar mapeo' : 'Activar mapeo' }}"
                                                >
                                                    {{ $map->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>

                                            {{-- Eliminar --}}
                                            <form
                                                method="POST"
                                                action="{{ route('admin.rem-mappings.destroy', $map) }}"
                                                class="inline"
                                                onsubmit="return confirm('¿Confirmas que deseas eliminar este mapeo de celda REM?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-status-bad-fg/30 bg-status-bad-bg px-2.5 py-1 text-xs font-semibold text-status-bad-fg hover:bg-status-bad-fg/15 transition-colors"
                                                    title="Eliminar mapeo"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-12 text-center text-sm text-[#64748B]">
                                        No se encontraron mapeos de celdas REM registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginación REM --}}
                @if ($remMappings->hasPages() || $remMappings->total() > 0)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-6 py-3.5 text-sm text-[#64748B]">
                        <span>Mostrando <strong class="text-brand-950">{{ $remMappings->firstItem() ?? 0 }}–{{ $remMappings->lastItem() ?? 0 }}</strong> de {{ $remMappings->total() }} mapeos configurados</span>
                        {{ $remMappings->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endif

</div>

{{-- Modales de administración --}}
@include('admin.partials.modal-indicador')
@include('admin.partials.modal-rem-mapping')

@endsection

@push('scripts')
<script>
    (() => {
        // ==========================================
        // Modal Indicador (Metas e IAAPS)
        // ==========================================
        const modalIndicador = document.getElementById('modal-admin-indicador');
        const formIndicador = document.getElementById('form-admin-indicador');

        const abrirModalIndicador = () => {
            modalIndicador.classList.replace('hidden', 'flex');
            (modalIndicador.querySelector('input:not([type="hidden"]), select') ?? modalIndicador.querySelector('button'))?.focus();
        };

        const cerrarModalIndicador = () => {
            modalIndicador.classList.replace('flex', 'hidden');
        };

        modalIndicador?.addEventListener('click', (e) => {
            if (e.target === modalIndicador || e.target.closest('[data-cerrar-modal-indicador]')) {
                cerrarModalIndicador();
            }
        });

        document.querySelector('[data-abrir-nuevo-indicador]')?.addEventListener('click', () => {
            document.getElementById('form-admin-indicador-titulo').textContent = 'Nueva Meta / Indicador IAAPS';
            formIndicador.action = formIndicador.dataset.urlCrear;
            formIndicador.elements._method.disabled = true;
            formIndicador.elements.indicador_id.value = '';

            formIndicador.elements.codigo.value = '';
            formIndicador.elements.nombre.value = '';
            formIndicador.elements.tipo.value = 'Meta Sanitaria';
            formIndicador.elements.target_value.value = '85';
            formIndicador.elements.periodicidad.value = 'Mensual';
            formIndicador.elements.fu.value = 'REM-A';
            formIndicador.elements.numerador_description.value = '';
            formIndicador.elements.denominador_description.value = '';
            formIndicador.elements.is_active.value = '1';

            abrirModalIndicador();
        });

        document.getElementById('iaaps-admin-filas')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-accion="editar-indicador"]');
            if (!btn) return;

            const tr = btn.closest('tr');
            if (!tr || !tr.dataset.indicador) return;

            const datos = JSON.parse(tr.dataset.indicador);
            document.getElementById('form-admin-indicador-titulo').textContent = `Editar indicador ${datos.codigo}`;
            formIndicador.action = datos.url_actualizar;
            formIndicador.elements._method.disabled = false;
            formIndicador.elements.indicador_id.value = datos.id;

            formIndicador.elements.codigo.value = datos.codigo ?? '';
            formIndicador.elements.nombre.value = datos.nombre ?? '';
            formIndicador.elements.tipo.value = datos.tipo ?? 'Meta Sanitaria';
            formIndicador.elements.target_value.value = datos.target_value ?? '85';
            formIndicador.elements.periodicidad.value = datos.periodicidad ?? 'Mensual';
            formIndicador.elements.fu.value = datos.fu ?? 'REM-A';
            formIndicador.elements.numerador_description.value = datos.numerador_description ?? '';
            formIndicador.elements.denominador_description.value = datos.denominador_description ?? '';
            formIndicador.elements.is_active.value = datos.is_active ? '1' : '0';

            abrirModalIndicador();
        });

        // ==========================================
        // Modal Mapeo REM
        // ==========================================
        const modalRem = document.getElementById('modal-admin-rem-mapping');
        const formRem = document.getElementById('form-admin-rem');

        const abrirModalRem = () => {
            modalRem.classList.replace('hidden', 'flex');
            (modalRem.querySelector('select, input:not([type="hidden"])') ?? modalRem.querySelector('button'))?.focus();
        };

        const cerrarModalRem = () => {
            modalRem.classList.replace('flex', 'hidden');
        };

        modalRem?.addEventListener('click', (e) => {
            if (e.target === modalRem || e.target.closest('[data-cerrar-modal-rem]')) {
                cerrarModalRem();
            }
        });

        document.querySelector('[data-abrir-nuevo-rem]')?.addEventListener('click', () => {
            document.getElementById('form-admin-rem-titulo').textContent = 'Nuevo Mapeo de Celda REM';
            formRem.action = formRem.dataset.urlCrear;
            formRem.elements._method.disabled = true;
            formRem.elements.mapping_id.value = '';

            formRem.elements.indicadores_id.value = '';
            formRem.elements.hoja.value = '';
            formRem.elements.celda_numerador.value = '';
            formRem.elements.celda_denominador.value = '';
            formRem.elements.seccion.value = '';
            formRem.elements.descripcion.value = '';
            formRem.elements.is_active.value = '1';

            abrirModalRem();
        });

        document.getElementById('rem-admin-filas')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-accion="editar-rem"]');
            if (!btn) return;

            const tr = btn.closest('tr');
            if (!tr || !tr.dataset.mapping) return;

            const datos = JSON.parse(tr.dataset.mapping);
            document.getElementById('form-admin-rem-titulo').textContent = `Editar Mapeo REM-${datos.hoja}`;
            formRem.action = datos.url_actualizar;
            formRem.elements._method.disabled = false;
            formRem.elements.mapping_id.value = datos.id;

            formRem.elements.indicadores_id.value = datos.indicadores_id ?? '';
            formRem.elements.hoja.value = datos.hoja ?? '';
            formRem.elements.celda_numerador.value = datos.celda_numerador ?? '';
            formRem.elements.celda_denominador.value = datos.celda_denominador ?? '';
            formRem.elements.seccion.value = datos.seccion ?? '';
            formRem.elements.descripcion.value = datos.descripcion ?? '';
            formRem.elements.is_active.value = datos.is_active ? '1' : '0';

            abrirModalRem();
        });

        // Cierre con Escape para cualquier modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (modalIndicador?.classList.contains('flex')) cerrarModalIndicador();
                if (modalRem?.classList.contains('flex')) cerrarModalRem();
            }
        });
    })();
</script>
@endpush