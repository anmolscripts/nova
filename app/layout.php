<?php

/** @var string $slot */

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(config('app.name'), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; background: #f7f7f4; color: #181816; }
        main { max-width: 880px; margin: 0 auto; padding: 72px 24px; }
        a { color: #0f766e; }
        code { background: #ecebe5; padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <main><?= $slot ?></main>
</body>
</html>
