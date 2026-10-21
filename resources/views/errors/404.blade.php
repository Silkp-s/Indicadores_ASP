@extends('errors.layout')

@section('title', 'Página o Registro No Encontrado')
@section('code', '404')

@section('badge_bg', 'bg-amber-500/10 text-amber-400')
@section('badge_ring', 'ring-amber-500/20')
@section('badge_color', 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/30')

@section('icon')
<svg class="h-10 w-10 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 9v2m0 2h.01" />
</svg>
@endsection

@section('message', !empty(($exception ?? null)?->getMessage()) ? $exception->getMessage() : 'La página, informe o registro al que intentas acceder no existe, ha cambiado de dirección o fue eliminado del sistema.')

@section('swal_title', 'Recurso No Encontrado (404)')
@section('swal_text', !empty(($exception ?? null)?->getMessage()) ? $exception->getMessage() : 'La página, informe o registro solicitado no se encuentra disponible en la plataforma.')
@section('swal_icon', 'warning')
