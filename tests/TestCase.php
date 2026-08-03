<?php

namespace Tests;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Verify the real test connection before Laravel can run database traits.
     */
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $app->make(DatabaseSafetyGuard::class)->assertTestEnvironmentSafe();

        return $app;
    }
}
