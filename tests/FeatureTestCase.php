<?php

namespace Ninja\DeviceTracker\Tests;

use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class FeatureTestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function setConfig(array $config = []): void
    {
        foreach ($config as $key => $value) {
            Config::set($key, $value);
        }
    }
}
