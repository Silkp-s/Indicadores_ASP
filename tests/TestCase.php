<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ningún test debe salir a internet (p. ej. descargas reales del DEIS); las respuestas se simulan con Http::fake().
        Http::preventStrayRequests();
    }
}
