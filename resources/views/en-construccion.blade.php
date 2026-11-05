@extends('layouts.app')

@section('title', $titulo)
@section('page-title', $titulo)

{{--
    Placeholder para los módulos del sidebar que aún no se maquetan:
    mantiene al usuario dentro del layout (sidebar y barra superior) en vez de la portada de Laravel.
--}}

@section('content')
<div class="rounded-xl border border-[#E2E8F0] bg-white px-6 py-16 text-center shadow-sm">
    <p class="text-base font-semibold text-brand-950">Módulo en construcción</p>
    <p class="mt-1 text-sm text-[#64748B]">Esta sección estará disponible próximamente.</p>
</div>
@endsection
