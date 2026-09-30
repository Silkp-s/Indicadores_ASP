@extends('layouts.app')

@section('title', 'Panel de Administración')
@section('page-title', 'Panel de Administración')
@section('page-subtitle', 'Gestión de usuarios y roles del sistema')

@section('content')
<div class="space-y-6">

    {{-- Header con acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-brand-950">Lista de usuarios</h2>
            <p class="text-sm text-[#6B7A75]">Administra los accesos y roles del sistema</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="flex items-center gap-2 rounded-md bg-brand-950 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Nuevo usuario
            </button>
        </div>
    </div>

    {{-- Tabla de usuarios --}}
    <div class="rounded-md border border-[#E1E7E4] bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#F9FBFA] border-b border-[#E1E7E4]">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Usuario</th>
                        <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Email</th>
                        <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Roles</th>
                        <th class="px-5 py-3.5 text-left text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Registro</th>
                        <th class="px-5 py-3.5 text-right text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E9EEEC]">
                    @forelse ($users as $user)
                    <tr class="hover:bg-[#F9FBFA] transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-950">
                                    {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-brand-950">{{ $user->name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-[#4C5B56]">{{ $user->email }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($user->roles as $role)
                                    @php
                                        $roleClass = match($role->name) {
                                            'Admin' => 'bg-brand-100 text-brand-950',
                                            'Usuario' => 'bg-status-ok-bg text-status-ok-fg',
                                            default => 'bg-[#E9EEEC] text-[#4C5B56]',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roleClass }}">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-sm text-[#6B7A75]">{{ $user->created_at->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                    class="rounded-md border border-[#D7DEDB] bg-white px-3 py-1.5 text-xs font-medium text-[#4C5B56] hover:bg-[#F4F6F5] transition-colors"
                                    title="Editar"
                                >
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('¿Eliminar este usuario?')">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="rounded-md border border-status-bad-fg/30 bg-status-bad-bg px-3 py-1.5 text-xs font-medium text-status-bad-fg hover:bg-status-bad-fg/10 transition-colors"
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
                        <td colspan="5" class="px-5 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-[#C7D3CE]" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                            <p class="mt-3 text-sm text-[#6B7A75]">No hay usuarios registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($users->hasPages())
        <div class="border-t border-[#E1E7E4] px-5 py-4">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Estadísticas rápidas --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Total usuarios</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">{{ $users->total() }}</p>
        </div>
        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Administradores</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">{{ \App\Models\User::role('Admin')->count() }}</p>
        </div>
        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Usuarios regulares</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">{{ \App\Models\User::role('Usuario')->count() }}</p>
        </div>
        <div class="rounded-md border border-[#E1E7E4] bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-[#6B7A75]">Registrados hoy</p>
            <p class="mt-3 text-2xl font-semibold text-brand-950">{{ \App\Models\User::whereDate('created_at', today())->count() }}</p>
        </div>
    </div>

</div>
@endsection