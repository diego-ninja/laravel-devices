<?php

namespace Ninja\DeviceTracker\Tests;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use WithWorkbench;
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setUp();
        $this->setConfig([
            'devices.authenticatable_class' => User::class,
        ]);
    }

    protected function setConfig(array $config = []): void
    {
        foreach ($config as $key => $value) {
            Config::set($key, $value);
        }
    }
}
