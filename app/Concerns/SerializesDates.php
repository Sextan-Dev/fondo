<?php

namespace App\Concerns;

trait SerializesDates
{
    protected function serializeDate(\DateTimeInterface $date)
    {
        // Keep the date format expected by the existing Vue application.
        return $date->format('Y-m-d H:i:s');
    }
}
