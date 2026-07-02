<?php

declare(strict_types=1);

namespace Nova\View;

use Latte\Extension;

/**
 * Registers Nova asset tags with Latte.
 */
final class LatteAssetExtension extends Extension
{
    public function getTags(): array
    {
        return [
            'asset' => LatteAssetNode::create(...),
            'component' => LatteComponentNode::create(...),
        ];
    }

    public function getFilters(): array
    {
        return [
            'default' => fn (mixed $value, mixed $default): mixed => ($value === null || $value === '') ? $default : $value,
        ];
    }
}
