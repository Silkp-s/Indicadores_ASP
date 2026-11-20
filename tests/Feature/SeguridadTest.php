<?php

use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Usuario']);
    Role::firstOrCreate(['name' => 'Admin']);

    $this->usuario = User::factory()->create();
    $this->usuario->assignRole('Usuario');

    $this->establecimiento = Establecimiento::create([
        'nombre' => 'CESFAM Seguridad',
        'codigo_deis' => '999999',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 10000,
        'is_active' => true,
    ]);
});

test('el inicio de sesion limita los intentos fallidos para evitar fuerza bruta', function () {
    Cache::flush();

    for ($intento = 1; $intento <= 6; $intento++) {
        $this->post('/', [
            'email' => 'atacante@example.com',
            'password' => 'clave-incorrecta',
        ])->assertRedirect();
    }

    $this->post('/', [
        'email' => 'atacante@example.com',
        'password' => 'clave-incorrecta',
    ])->assertStatus(429);
});

test('la importacion rechaza archivos que no son planillas REM', function () {
    $archivo = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

    $this->actingAs($this->usuario)
        ->postJson(route('importacion.previsualizar'), ['archivo' => $archivo])
        ->assertStatus(422)
        ->assertJsonValidationErrors('archivo');
});

test('la importacion no permite confirmar una planilla procesada por otro usuario', function () {
    $otroUsuario = User::factory()->create();
    $otroUsuario->assignRole('Usuario');

    $tempToken = 'token-de-otro-usuario';
    Cache::put("rem_pending_{$tempToken}", [
        'user_id' => $otroUsuario->id,
        'establecimiento_id' => $this->establecimiento->id,
        'extracted' => [],
    ], now()->addHour());

    $this->actingAs($this->usuario)
        ->postJson(route('importacion.guardar'), [
            'temp_token' => $tempToken,
            'establecimiento_id' => $this->establecimiento->id,
            'año' => 2026,
            'mes' => 6,
        ])
        ->assertStatus(403);
});

test('la importacion no permite guardar datos en un establecimiento distinto al detectado', function () {
    $otroEstablecimiento = Establecimiento::create([
        'nombre' => 'CESFAM Distinto',
        'codigo_deis' => '888888',
        'tipo' => 'CESFAM',
        'poblacion_inscrita' => 5000,
        'is_active' => true,
    ]);

    $tempToken = 'token-establecimiento';
    Cache::put("rem_pending_{$tempToken}", [
        'user_id' => $this->usuario->id,
        'establecimiento_id' => $this->establecimiento->id,
        'extracted' => [],
    ], now()->addHour());

    $this->actingAs($this->usuario)
        ->postJson(route('importacion.guardar'), [
            'temp_token' => $tempToken,
            'establecimiento_id' => $otroEstablecimiento->id,
            'año' => 2026,
            'mes' => 6,
        ])
        ->assertStatus(422)
        ->assertJson(['status' => 'error']);
});

test('la importacion rechaza tokens expirados o inexistentes', function () {
    $this->actingAs($this->usuario)
        ->postJson(route('importacion.guardar'), [
            'temp_token' => 'token-que-no-existe',
            'establecimiento_id' => $this->establecimiento->id,
            'año' => 2026,
            'mes' => 6,
        ])
        ->assertStatus(422)
        ->assertJson(['status' => 'error']);
});

test('los mensajes flash se escapan antes de inyectarse en javascript', function () {
    $payload = '</script><script>alert(1)</script>';

    $this->actingAs($this->usuario)
        ->withSession(['status' => $payload])
        ->get(route('centro-mando'))
        ->assertOk()
        ->assertDontSee($payload, false);
});

test('todas las respuestas llevan las cabeceras de seguridad', function () {
    $respuesta = $this->get('/');

    $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('un administrador no puede quitarse su propio rol de administrador', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'apellido' => 'DAS',
            'email' => $admin->email,
            'role' => 'Usuario',
        ])
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->hasRole('Admin'))->toBeTrue();
});

test('un periodo manipulado en la url no rompe las paginas', function (string $ruta) {
    $this->actingAs($this->usuario)
        ->get(route($ruta).'?periodo[]=2026-06')
        ->assertOk();

    $this->actingAs($this->usuario)
        ->get(route($ruta, ['periodo' => '2026-06/../x']))
        ->assertOk();
})->with(['centro-mando', 'iaaps-metas.index', 'alertas.index']);

test('la importacion de planillas tiene un limite de uso por minuto', function () {
    Cache::flush();

    for ($intento = 1; $intento <= 10; $intento++) {
        $this->actingAs($this->usuario)
            ->postJson(route('importacion.previsualizar'))
            ->assertStatus(422);
    }

    $this->actingAs($this->usuario)
        ->postJson(route('importacion.previsualizar'))
        ->assertStatus(429);
});

test('en produccion el seeder no deja la clave publica "password" en las cuentas', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $admin = User::where('email', 'admin@das.cl')->firstOrFail();

    expect(Hash::check('password', $admin->password))->toBeFalse()
        ->and($admin->hasRole('Admin'))->toBeTrue();
});
