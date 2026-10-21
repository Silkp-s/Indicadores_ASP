@extends('errors.layout')

@section('title', 'Acceso Restringido o Denegado')
@section('code', '403')

@section('badge_bg', 'bg-rose-500/10 text-rose-400')
@section('badge_ring', 'ring-rose-500/20')
@section('badge_color', 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30')

@section('icon')
<svg class="h-10 w-10 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
</svg>
@endsection

@section('message', !empty(($exception ?? null)?->getMessage()) ? $exception->getMessage() : 'No dispones de los permisos o privilegios necesarios para acceder a esta área o ejecutar esta acción en la plataforma.')

@section('swal_title', 'Acceso Denegado (403)')
@section('swal_text', !empty(($exception ?? null)?->getMessage()) ? $exception->getMessage() : 'No dispones de los permisos o privilegios requeridos para acceder a esta sección de la plataforma.')
@section('swal_icon', 'error')
