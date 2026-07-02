<?php

declare(strict_types=1);

namespace Nova\Routing;

use Nova\App\Page;

/**
 * Carries the matched page and route parameters.
 */
final class RouteMatch
{
    public function __construct(
        public readonly Page $page,
        public readonly array $parameters
    ) {
    }
}
