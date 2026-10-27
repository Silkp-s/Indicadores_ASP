<?php

use App\Models\Establecimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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
