<?php

declare(strict_types=1);

namespace App\Policies;

final class ProductPolicy
{
    public function update(?array $user, object|array $product): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }
}
