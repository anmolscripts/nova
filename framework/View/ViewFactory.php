<?php

declare(strict_types=1);

namespace Nova\View;

use Nova\Application\Application;

final class ViewFactory
{
    public function __construct(private readonly Application $app)
    {
    }

    public function render(string $name, array $data = []): string
    {
        if ($this->app->make(LatteEngine::class)->exists($name)) {
            return $this->app->make(LatteEngine::class)->render($name, $data);
        }

        $path = $this->resolve($name);
        $compiled = $this->compile($path);
        return $this->include($compiled, $data);
    }

    public function renderString(string $content): string
    {
        return '<pre>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</pre>';
    }

    public static function value(array $data, array $locals, string $expression, mixed $default = null): mixed
    {
        $expression = trim($expression);
        $segments = explode('.', $expression);
        $root = array_shift($segments);

        if (array_key_exists($root, $locals)) {
            $value = $locals[$root];
        } elseif (array_key_exists($root, $data)) {
            $value = $data[$root];
        } else {
            return $default;
        }

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }
            if (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
                continue;
            }
            return $default;
        }

        return $value;
    }

    private function resolve(string $name): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $name);
        foreach ($this->app->config()->get('view.paths', []) as $base) {
            foreach ([$relative . '.nova.php', $relative . '.php'] as $candidate) {
                $path = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $candidate;
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        throw new \RuntimeException("View [{$name}] not found.");
    }

    private function compile(string $path): string
    {
        $cache = $this->app->config()->get('view.cache', $this->app->storagePath('framework/views'));
        if (!is_dir($cache)) {
            mkdir($cache, 0777, true);
        }

        $compiled = $cache . DIRECTORY_SEPARATOR . sha1($path) . '.php';
        if (is_file($compiled) && filemtime($compiled) >= filemtime($path)) {
            return $compiled;
        }

        $source = (string) file_get_contents($path);
        $php = $this->compileSource($source);
        file_put_contents($compiled, $php);

        return $compiled;
    }

    private function compileSource(string $source): string
    {
        $source = preg_replace_callback('/\{\{\s*(.+?)\s*}}/', fn ($m) => '<?= htmlspecialchars(\\Nova\\View\\ViewFactory::value($GLOBALS[\'__nova_view_data\'], get_defined_vars(), \'' . addslashes($m[1]) . '\', \'\'), ENT_QUOTES, \'UTF-8\') ?>', $source);
        $source = preg_replace_callback('/\{!!\s*(.+?)\s*!!}/', fn ($m) => '<?= \\Nova\\View\\ViewFactory::value($GLOBALS[\'__nova_view_data\'], get_defined_vars(), \'' . addslashes($m[1]) . '\', \'\') ?>', $source);
        $source = preg_replace_callback('/@if\((.+?)\)/', fn ($m) => '<?php if (' . $this->compileCondition($m[1]) . '): ?>', $source);
        $source = preg_replace_callback('/@elseif\((.+?)\)/', fn ($m) => '<?php elseif (' . $this->compileCondition($m[1]) . '): ?>', $source);
        $source = str_replace('@else', '<?php else: ?>', $source);
        $source = str_replace('@endif', '<?php endif; ?>', $source);
        $source = preg_replace_callback('/@foreach\((.+?)\s+as\s+(.+?)\)/', fn ($m) => '<?php foreach ((array) \\Nova\\View\\ViewFactory::value($GLOBALS[\'__nova_view_data\'], get_defined_vars(), \'' . addslashes(trim($m[1])) . '\', []) as $' . trim($m[2], '$ ') . '): ?>', $source);
        $source = str_replace('@endforeach', '<?php endforeach; ?>', $source);

        return $source;
    }

    private function compileCondition(string $condition): string
    {
        $condition = trim($condition);
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $condition)) {
            return "\\Nova\\View\\ViewFactory::value(\$GLOBALS['__nova_view_data'], get_defined_vars(), '" . addslashes($condition) . "', false)";
        }
        return $condition;
    }

    private function include(string $file, array $data): string
    {
        $GLOBALS['__nova_view_data'] = $data;
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        unset($GLOBALS['__nova_view_data']);
        return (string) ob_get_clean();
    }
}
