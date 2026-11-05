@extends('errors.layout')

@section('title', 'Página Expirada')
@section('code', '419')

@section('badge_bg', 'bg-amber-500/10 text-amber-400')
@section('badge_ring', 'ring-amber-500/20')
@section('badge_color', 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/30')

@section('icon')
<svg class="h-10 w-10 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection

@section('message', 'Tu sesión ha expirado por inactividad. Por favor recarga la página o inicia sesión de nuevo.')

@section('swal_title', 'Sesión Expirada (419)')
@section('swal_text', 'El token de seguridad ha caducado. Vuelve a iniciar sesión para continuar de forma segura.')
@section('swal_icon', 'warning')
