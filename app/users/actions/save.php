<?php

declare(strict_types=1);

validate([
    'name' => 'required',
]);

return success([
    'name' => request('name'),
], 'User saved successfully.');
