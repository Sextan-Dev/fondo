<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests use a disposable key and never load production credentials.
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32)), 'app.report_signing_key' => bin2hex(random_bytes(32)), 'jwt.secret' => bin2hex(random_bytes(32)), 'logging.default' => 'errorlog']);
    }
}
