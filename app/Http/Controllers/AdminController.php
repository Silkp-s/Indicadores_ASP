<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstablecimientoRequest;
use App\Http\Requests\IaapsMetaRequest;
use App\Http\Requests\RemMappingRequest;
use App\Models\Establecimiento;
use App\Models\Indicador;
use App\Models\RemMapping;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Muestra el panel de administración unificado con las 4 secciones:
     * 1. Gestión de Usuarios
     * 2. Metas Sanitarias e IAAPS
     * 3. Configuración de Mapeo de Celdas REM
     * 4. Establecimientos de Salud
     */
    public function index(Request $request): View
    {
        $tab = (string) $request->input('tab', 'usuarios');

        // 1. Sección Usuarios
        $qUsers = trim((string) $request->input('q_users', ''));
        $usersQuery = User::with('roles');

        if ($qUsers !== '') {
            $usersQuery->where(function ($q) use ($qUsers) {
                $q->where('name', 'like', "%{$qUsers}%")
                    ->orWhere('apellido', 'like', "%{$qUsers}%")
                    ->orWhere('email', 'like', "%{$qUsers}%");
            });
        }

        $users = $usersQuery->latest()->paginate(10, ['*'], 'page_users')->withQueryString();

        // 2. Sección Metas Sanitarias e IAAPS
        $qIaaps = trim((string) $request->input('q_iaaps', ''));
        $tipoIaaps = (string) $request->input('tipo_iaaps', 'todas');
        $estadoIaaps = (string) $request->input('estado_iaaps', 'todos');

        $iaapsQuery = Indicador::query();

        if ($tipoIaaps === 'Meta Sanitaria' || $tipoIaaps === 'IAAPS') {
            $iaapsQuery->where('tipo', $tipoIaaps);
        } else {
            $iaapsQuery->whereIn('tipo', ['Meta Sanitaria', 'IAAPS']);
        }

        if ($qIaaps !== '') {
            $iaapsQuery->where(function ($q) use ($qIaaps) {
                $q->where('codigo', 'like', "%{$qIaaps}%")
                    ->orWhere('nombre', 'like', "%{$qIaaps}%");
            });
        }

        if ($estadoIaaps === 'activo') {
            $iaapsQuery->where('is_active', true);
        } elseif ($estadoIaaps === 'inactivo') {
            $iaapsQuery->where('is_active', false);
        }

        $indicadores = $iaapsQuery->orderBy('tipo')->orderBy('codigo')->paginate(10, ['*'], 'page_iaaps')->withQueryString();

        // 3. Sección Mapeo de Celdas REM
        $qRem = trim((string) $request->input('q_rem', ''));
        $remQuery = RemMapping::with('indicador');

        if ($qRem !== '') {
            $remQuery->where(function ($q) use ($qRem) {
                $q->where('hoja', 'like', "%{$qRem}%")
                    ->orWhere('celda_numerador', 'like', "%{$qRem}%")
                    ->orWhere('celda_denominador', 'like', "%{$qRem}%")
                    ->orWhere('descripcion', 'like', "%{$qRem}%")
                    ->orWhereHas('indicador', function ($sub) use ($qRem) {
                        $sub->where('codigo', 'like', "%{$qRem}%")
                            ->orWhere('nombre', 'like', "%{$qRem}%");
                    });
            });
        }

        $remMappings = $remQuery->orderBy('hoja')->paginate(12, ['*'], 'page_rem')->withQueryString();

        // 4. Sección Establecimientos de Salud
        $qEst = trim((string) $request->input('q_est', ''));
        $tipoEst = (string) $request->input('tipo_est', '');
        $estadoEst = (string) $request->input('estado_est', 'todos');

        $filtrosEst = [
            'q' => $qEst,
            'tipo' => in_array($tipoEst, Establecimiento::TIPOS, true) ? $tipoEst : null,
            'estado' => in_array($estadoEst, ['activo', 'inactivo', 'todos'], true) ? $estadoEst : 'todos',
        ];

        $establecimientos = Establecimiento::query()
            ->withCount('medidas')
            ->filtrar($filtrosEst)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->paginate(12, ['*'], 'page_establecimientos')
            ->withQueryString();

        // Indicadores disponibles para vincular en mapeo REM
        $indicadoresDisponibles = Indicador::whereIn('tipo', ['Meta Sanitaria', 'IAAPS'])
            ->orderBy('tipo')
            ->orderBy('codigo')
            ->get();

        // Métricas / Estadísticas globales del panel
        $stats = [
            'total_usuarios' => User::count(),
            'total_admins' => User::role('Admin')->count(),
            'total_indicadores' => Indicador::whereIn('tipo', ['Meta Sanitaria', 'IAAPS'])->count(),
            'total_mapeos_rem' => RemMapping::count(),
            'mapeos_activos' => RemMapping::where('is_active', true)->count(),
            'total_establecimientos' => Establecimiento::count(),
        ];

        return view('admin.dashboard', [
            'tab' => $tab,
            'users' => $users,
            'qUsers' => $qUsers,
            'indicadores' => $indicadores,
            'qIaaps' => $qIaaps,
            'tipoIaaps' => $tipoIaaps,
            'estadoIaaps' => $estadoIaaps,
            'remMappings' => $remMappings,
            'qRem' => $qRem,
            'indicadoresDisponibles' => $indicadoresDisponibles,
            'establecimientos' => $establecimientos,
            'qEst' => $qEst,
            'tipoEst' => $tipoEst,
            'estadoEst' => $estadoEst,
            'tiposEstablecimiento' => Establecimiento::TIPOS,
            'stats' => $stats,
        ]);
    }

    // ==========================================
    // SECCIÓN 1: USUARIOS
    // ==========================================

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.dashboard', ['tab' => 'usuarios'])
            ->with('status', "Usuario {$user->name} {$user->apellido} creado correctamente.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->update([
            'name' => $request->name,
            'apellido' => $request->apellido,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.dashboard', ['tab' => 'usuarios'])
            ->with('status', "Usuario {$user->name} actualizado correctamente.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta de administrador.'])
                ->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'usuarios'])
            ->with('status', "Usuario {$name} eliminado correctamente.");
    }

    // ==========================================
    // SECCIÓN 2: METAS SANITARIAS E IAAPS
    // ==========================================

    public function storeIndicador(IaapsMetaRequest $request): RedirectResponse
    {
        $indicador = Indicador::create($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'iaaps', 'q_iaaps' => $indicador->codigo])
            ->with('status', "Indicador {$indicador->codigo} (“{$indicador->nombre}”) registrado exitosamente.");
    }

    public function updateIndicador(IaapsMetaRequest $request, Indicador $indicador): RedirectResponse
    {
        $indicador->update($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'iaaps'])
            ->with('status', "Indicador {$indicador->codigo} actualizado exitosamente.");
    }

    public function destroyIndicador(Indicador $indicador): RedirectResponse
    {
        $codigo = $indicador->codigo;
        $nombre = $indicador->nombre;

        $indicador->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'iaaps'])
            ->with('status', "Indicador {$codigo} (“{$nombre}”) eliminado exitosamente.");
    }

    public function cambiarEstadoIndicador(Request $request, Indicador $indicador): RedirectResponse
    {
        $datos = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $indicador->update(['is_active' => (bool) $datos['is_active']]);
        $accion = $indicador->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.dashboard', ['tab' => 'iaaps'])
            ->with('status', "Indicador {$indicador->codigo} {$accion} correctamente.");
    }

    // ==========================================
    // SECCIÓN 3: MAPEO DE CELDAS REM
    // ==========================================

    public function storeRemMapping(RemMappingRequest $request): RedirectResponse
    {
        $mapping = RemMapping::create($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'rem'])
            ->with('status', "Mapeo para hoja REM-{$mapping->hoja} guardado correctamente.");
    }

    public function updateRemMapping(RemMappingRequest $request, RemMapping $mapping): RedirectResponse
    {
        $mapping->update($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'rem'])
            ->with('status', "Mapeo REM-{$mapping->hoja} actualizado correctamente.");
    }

    public function destroyRemMapping(RemMapping $mapping): RedirectResponse
    {
        $hoja = $mapping->hoja;
        $codigo = $mapping->indicador?->codigo ?? 'Indicador';

        $mapping->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'rem'])
            ->with('status', "Mapeo de celda REM para {$codigo} (Hoja {$hoja}) eliminado correctamente.");
    }

    public function cambiarEstadoRemMapping(Request $request, RemMapping $mapping): RedirectResponse
    {
        $datos = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $mapping->update(['is_active' => (bool) $datos['is_active']]);
        $accion = $mapping->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.dashboard', ['tab' => 'rem'])
            ->with('status', "Mapeo REM-{$mapping->hoja} {$accion} correctamente.");
    }

    // ==========================================
    // SECCIÓN 4: ESTABLECIMIENTOS DE SALUD
    // ==========================================

    public function storeEstablecimiento(EstablecimientoRequest $request): RedirectResponse
    {
        $establecimiento = Establecimiento::create($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'establecimientos', 'q_est' => $establecimiento->codigo_deis ?: $establecimiento->nombre])
            ->with('status', "Establecimiento “{$establecimiento->nombre}” creado exitosamente.");
    }

    public function updateEstablecimiento(EstablecimientoRequest $request, Establecimiento $establecimiento): RedirectResponse
    {
        $establecimiento->update($request->validated());

        return redirect()->route('admin.dashboard', ['tab' => 'establecimientos'])
            ->with('status', "Establecimiento “{$establecimiento->nombre}” actualizado exitosamente.");
    }

    public function destroyEstablecimiento(Establecimiento $establecimiento): RedirectResponse
    {
        $conteoMedidas = $establecimiento->medidas()->count();

        if ($conteoMedidas > 0) {
            return back()->withErrors([
                'establecimiento' => "No se puede eliminar “{$establecimiento->nombre}” porque cuenta con {$conteoMedidas} mediciones registradas en el sistema. Puedes desactivarlo para que no figure en nuevas operaciones.",
            ]);
        }

        $nombre = $establecimiento->nombre;
        $establecimiento->delete();

        return redirect()->route('admin.dashboard', ['tab' => 'establecimientos'])
            ->with('status', "Establecimiento “{$nombre}” eliminado exitosamente.");
    }

    public function cambiarEstadoEstablecimiento(Request $request, Establecimiento $establecimiento): RedirectResponse
    {
        $datos = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $establecimiento->update(['is_active' => (bool) $datos['is_active']]);
        $accion = $establecimiento->is_active ? 'activado' : 'desactivado';

        return redirect()->route('admin.dashboard', ['tab' => 'establecimientos'])
            ->with('status', "Establecimiento “{$establecimiento->nombre}” {$accion} exitosamente.");
    }
}
