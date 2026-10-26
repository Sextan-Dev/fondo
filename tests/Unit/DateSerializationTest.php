<?php

namespace Tests\Unit;

use App\Operation;
use App\User;
use Tests\TestCase;

class DateSerializationTest extends TestCase
{
    public function test_existing_date_format_is_preserved(): void
    {
        foreach ([new Operation(), new User()] as $model) {
            $model->setRawAttributes(['created_at' => '2026-10-20 15:30:45']);
            $this->assertSame('2026-10-20 15:30:45', $model->toArray()['created_at']);
        }
    }
}
