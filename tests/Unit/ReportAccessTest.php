<?php

namespace Tests\Unit;

use App\Support\ReportAccess;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    public function test_signed_link_is_limited_to_one_report(): void
    {
        $token = ReportAccess::token('donor', 123);
        $this->assertSame(123, ReportAccess::resolve(new Request(['id' => $token]), 'donor'));
        $this->assertNull(ReportAccess::resolve(new Request(['id' => $token]), 'ally'));
        $this->assertNull(ReportAccess::resolve(new Request(['id' => substr($token, 0, -3).'124']), 'donor'));
    }

    public function test_guessable_links_are_refused_without_authentication(): void
    {
        foreach (['123', 'random-prefixhe5123', 'random-prefixca68123'] as $token) {
            $this->assertNull(ReportAccess::resolve(new Request(['id' => $token]), 'donor'));
            $this->assertNull(ReportAccess::resolve(new Request(['id' => $token]), 'ally'));
        }
    }
}
