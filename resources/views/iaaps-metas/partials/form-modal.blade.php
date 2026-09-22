{{--
    Modal de Crear/Editar indicador.
    Toggle con vanilla JS (classList) para no asumir Alpine/Livewire todavía.
    Si el proyecto ya trae Alpine (Breeze), se puede reemplazar por x-data fácilmente.
--}}
<div id="modal-indicador" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">

        <div class="flex items-center justify-between border-b border-[#E1E7E4] px-6 py-4">
            <h2 class="text-base font-semibold text-brand-950">Nuevo indicador</h2>
            <button type="button" onclick="document.getElementById('modal-indicador').classList.add('hidden')" class="text-[#9AA6A1] hover:text-brand-950">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('iaaps-metas.store') }}" class="space-y-4 px-6 py-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#1C2A26]">Código</label>
                    <input type="text" name="codigo" placeholder="IAAPS-05" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#1C2A26]">Tipo</label>
                    <select name="tipo" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
                        <option>IAAPS</option>
                        <option>Meta Sanitaria</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#1C2A26]">Nombre del indicador</label>
                <input type="text" name="nombre" placeholder="Ej: Cobertura Efectiva DM2 en personas de 15 años y más" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#1C2A26]">Meta (%)</label>
                    <input type="number" name="meta" min="0" max="100" placeholder="85" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#1C2A26]">Avance físico (%)</label>
                    <input type="number" name="avance" min="0" max="100" placeholder="55" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#1C2A26]">Estado</label>
                    <select name="estado" class="mt-1.5 w-full rounded-md border border-[#D7DEDB] px-3 py-2 text-sm focus:border-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-800/20">
                        <option>Activo</option>
                        <option>Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-[#E1E7E4] pt-4">
                <button type="button" onclick="document.getElementById('modal-indicador').classList.add('hidden')" class="rounded-md px-4 py-2 text-sm font-medium text-[#4C5B56] hover:bg-[#F4F6F5]">
                    Cancelar
                </button>
                <button type="submit" class="rounded-md bg-brand-950 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800">
                    Guardar indicador
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    #modal-indicador:not(.hidden) { display: flex; }
</style>
