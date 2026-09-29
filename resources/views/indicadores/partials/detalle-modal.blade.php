{{--
    Modal "Detalle": ficha técnica del indicador (solo lectura).
    Se llena con JS desde el data-indicador de la fila (ver el script de indicadores.index):
    cada [data-campo] recibe el valor de esa clave, con [data-sufijo] opcional.
--}}
<div id="modal-catalogo-detalle" data-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-brand-950/50 px-4" role="dialog" aria-modal="true" aria-labelledby="detalle-titulo">
    <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">

        <div class="flex items-start justify-between gap-4 border-b border-[#E2E8F0] px-6 py-5">
            <div>
                <div class="flex items-center gap-2">
                    <span data-campo="codigo" class="rounded-md bg-brand-950 px-2 py-0.5 text-xs font-semibold text-white"></span>
                    <span data-campo="tipo" class="text-xs font-medium uppercase tracking-wide text-[#64748B]"></span>
                </div>
                <h2 id="detalle-titulo" data-campo="nombre" class="mt-2 text-lg font-semibold leading-snug text-brand-950"></h2>
            </div>
            <button type="button" data-cerrar class="text-[#94A3B8] hover:text-brand-950" aria-label="Cerrar">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Numerador</p>
                    <p data-campo="numerador_description" class="mt-1.5 text-sm text-[#1E293B]"></p>
                </div>
                <div class="rounded-lg bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Denominador</p>
                    <p data-campo="denominador_description" class="mt-1.5 text-sm text-[#1E293B]"></p>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Meta anual</dt>
                    <dd data-campo="meta_formateada" data-sufijo="%" class="mt-1 text-sm font-semibold text-brand-950"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Periodicidad</dt>
                    <dd data-campo="periodicidad" class="mt-1 text-sm font-semibold text-brand-950"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Fuente</dt>
                    <dd data-campo="fu" class="mt-1 text-sm font-semibold text-brand-950"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Estado</dt>
                    <dd data-campo="estado_label" class="mt-1 text-sm font-semibold text-brand-950"></dd>
                </div>
            </dl>

            <div class="flex items-center justify-between rounded-lg border border-dashed border-[#CBD5E1] px-4 py-3 text-sm">
                <span class="font-medium text-[#334155]">Cumplimiento actual</span>
                <span class="text-[#64748B]">Sin mediciones registradas</span>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-[#E2E8F0] px-6 py-4">
            <button type="button" data-cerrar class="rounded-lg px-4 py-2 text-sm font-medium text-[#475569] hover:bg-[#F1F5F9]">
                Cerrar
            </button>
            <button type="button" data-editar-desde-detalle class="rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-white hover:bg-accent-600">
                Editar indicador
            </button>
        </div>
    </div>
</div>
