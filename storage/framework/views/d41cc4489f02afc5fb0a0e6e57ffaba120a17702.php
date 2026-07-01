<h1><?= htmlspecialchars(\Nova\View\ViewFactory::value($GLOBALS['__nova_view_data'], get_defined_vars(), 'title', ''), ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars(\Nova\View\ViewFactory::value($GLOBALS['__nova_view_data'], get_defined_vars(), 'message', ''), ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/about">Visit /about</a> or <a href="/api/ping">GET /api/ping</a>.</p>
