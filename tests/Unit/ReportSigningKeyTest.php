<?php

namespace Tests\Unit;

use App\Support\ReportAccess;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportSigningKeyTest extends TestCase
{
    public function test_report_links_use_an_independent_key(): void
    {
        config(['app.report_signing_key' => str_repeat('a', 64), 'app.key' => str_repeat('b', 64)]);
        $token = ReportAccess::token('donor', 123);
        config(['app.key' => str_repeat('c', 64)]);
        $this->assertSame($token, ReportAccess::token('donor', 123));
        config(['app.report_signing_key' => str_repeat('d', 64)]);
        $this->assertNotSame($token, ReportAccess::token('donor', 123));
        $this->assertNull(ReportAccess::resolve(new Request(['id' => $token, 'token' => $token]), 'donor'));
        $valid = ReportAccess::token('donor', 123);
        $this->assertSame(123, ReportAccess::resolve(new Request(['id' => $valid, 'token' => $valid]), 'donor'));
    }

    public function test_a_missing_report_key_fails_closed(): void
    {
        config(['app.report_signing_key' => null]);
        $this->expectException(\LogicException::class);
        ReportAccess::token('donor', 123);
    }
}
