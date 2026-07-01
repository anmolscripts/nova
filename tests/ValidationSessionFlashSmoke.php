<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
app($app);
app()->session()->start();

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

session('notice', 'Session works');
$assert(session('notice') === 'Session works', 'session(key,value) or session(key) failed.');
forget('notice');
$assert(session('notice') === null, 'forget() failed.');
session('notice', 'Session works');
regenerate();

success('Saved');
warning('Careful');
info('Heads up');
danger('Broken');
$assert(flash('success') === 'Saved', 'success flash failed.');
$assert(flash('warning') === 'Careful', 'warning flash failed.');
$assert(flash('info') === 'Heads up', 'info flash failed.');
$assert(flash('danger') === 'Broken', 'danger flash failed.');

$valid = ValidatorAlias::validate([
    'name' => 'Ada',
    'email' => 'ada@example.com',
    'age' => '36',
    'score' => '99.5',
    'active' => '1',
    'website' => 'https://example.com',
    'date' => '2026-07-01',
    'password' => 'secret',
    'password_confirmation' => 'secret',
    'role' => 'admin',
    'tags' => ['php'],
], [
    'name' => 'required|string|min:3|max:10',
    'email' => 'required|email',
    'age' => 'integer|min:18',
    'score' => 'numeric|between:1,100',
    'active' => 'boolean',
    'website' => 'url',
    'date' => 'date',
    'password' => 'confirmed',
    'role' => 'in:admin,user|not_in:banned',
    'tags' => 'array',
]);
$assert($valid['name'] === 'Ada', 'Valid rules failed.');

try {
    ValidatorAlias::validate([
        'name' => 'Al',
        'email' => 'bad',
        'age' => '17',
        'password' => 'secret',
        'password_confirmation' => 'different',
    ], [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'age' => 'integer|min:18',
        'password' => 'confirmed',
    ], [
        'email.email' => 'Email is not valid.',
    ]);
    $assert(false, 'Invalid validation should throw.');
} catch (\Nova\Validation\ValidationException $exception) {
    $assert(isset($exception->errors['name']), 'Name validation error missing.');
    $assert(($exception->errors['email'][0] ?? null) === 'Email is not valid.', 'Custom message failed.');
}

$old = old();
$assert(($old['name'] ?? null) === 'Al', 'Old input was not flashed.');
$assert(!isset($old['password'], $old['password_confirmation']), 'Sensitive old input was not filtered.');
$assert(errors()->has('email'), 'Error bag has() failed.');
$assert(errors()->first('email') === 'Email is not valid.', 'Error bag first() failed.');

app()->setRequest(new \Nova\Http\Request('GET', '/', [], [], [], [], []));
flash('_old_input', ['email' => 'old@example.com']);
$assert(request('email') === 'old@example.com', 'request(field) did not fallback to old input.');

session('notice', 'Session works');
$html = app()->make(\Nova\View\LatteEngine::class)->render(dirname(__DIR__) . '/tests/fixtures/validation.latte');
$assert(str_contains($html, 'Email is not valid.'), 'Latte errors() helper failed.');
$assert(str_contains($html, 'old@example.com'), 'Latte old() helper failed.');
$assert(str_contains($html, 'Session works'), 'Latte session() helper failed.');

echo "Validation/session/flash smoke test passed." . PHP_EOL;

final class ValidatorAlias
{
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        return \Nova\Validation\Validator::make($data, $rules, $messages)->validate();
    }
}
