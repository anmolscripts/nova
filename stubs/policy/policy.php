<?php

declare(strict_types=1);

namespace App\Policies;

final class {{ class }}
{
    public function view(?array $user, mixed $subject = null): bool
    {
        return $user !== null;
    }
}
