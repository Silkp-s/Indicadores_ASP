@extends('errors.layout')

@section('title', 'Error Interno del Servidor')
@section('code', '500')

@section('badge_bg', 'bg-rose-500/10 text-rose-400')
@section('badge_ring', 'ring-rose-500/20')
@section('badge_color', 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-500/30')

@section('icon')
<svg class="h-10 w-10 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
</svg>
@endsection

@section('message', 'Ocurrió un error inesperado al procesar tu solicitud en el servidor. Por favor intenta nuevamente.')

@section('swal_title', 'Error del Servidor (500)')
@section('swal_text', 'Ocurrió un problema interno en el servidor al procesar la solicitud.')
@section('swal_icon', 'error')
