<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$logPath = dirname(__DIR__) . '/storage/logs/database-test.log';
@unlink($logPath);

$db = new \Nova\Database\DatabaseManager([
    'default' => 'sqlite',
    'logging' => [
        'enabled' => true,
        'path' => $logPath,
    ],
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ],
]);

$assert = function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$connection = $db->connection();
$connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, status TEXT NOT NULL, age INTEGER NOT NULL)');

$assert($db->insert('users', ['name' => 'Ada', 'status' => 'ACTIVE', 'age' => 36]) === true, 'Insert failed.');
$db->insert('users', ['name' => 'Linus', 'status' => 'ACTIVE', 'age' => 55]);
$db->insert('users', ['name' => 'Grace', 'status' => 'INACTIVE', 'age' => 40]);

$rows = $db->select('SELECT * FROM users WHERE status = ? ORDER BY id', ['ACTIVE']);
$assert(count($rows) === 2, 'Raw select failed.');

$first = $db->first('SELECT * FROM users WHERE name = ?', ['Ada']);
$assert($first !== null && $first['name'] === 'Ada', 'First failed.');

$scalar = $db->scalar('SELECT COUNT(*) FROM users WHERE status = ?', ['ACTIVE']);
$assert((int) $scalar === 2, 'Scalar failed.');

$builderRows = $db->table('users')
    ->select(['name', 'age'])
    ->where('status', 'ACTIVE')
    ->whereIn('name', ['Ada', 'Linus'])
    ->orderBy('age', 'DESC')
    ->limit(1)
    ->offset(0)
    ->get();
$assert($builderRows[0]['name'] === 'Linus', 'Builder get/order/limit failed.');

$orWhere = $db->table('users')
    ->where('name', 'Ada')
    ->orWhere('name', 'Grace')
    ->count();
$assert($orWhere === 2, 'orWhere/count failed.');

$grouped = $db->table('users')
    ->select(['status', 'COUNT(*) AS total'])
    ->groupBy('status')
    ->orderBy('status')
    ->get();
$assert(count($grouped) === 2, 'groupBy failed.');

$exists = $db->table('users')->where('name', 'Ada')->exists();
$assert($exists === true, 'Exists failed.');

$missing = $db->table('users')->where('name', 'Missing')->first();
$assert($missing === null, 'Builder first should return null for missing row.');

$updated = $db->update('users', ['status' => 'INACTIVE'], ['name' => 'Linus']);
$assert($updated === 1, 'Update failed.');

$deleted = $db->delete('users', ['name' => 'Grace']);
$assert($deleted === 1, 'Delete failed.');

$db->transaction(function (\Nova\Database\Connection $connection): void {
    $connection->insert('users', ['name' => 'Transaction', 'status' => 'ACTIVE', 'age' => 1]);
});
$assert($db->table('users')->where('name', 'Transaction')->exists(), 'Transaction commit failed.');

try {
    $db->transaction(function (\Nova\Database\Connection $connection): void {
        $connection->insert('users', ['name' => 'Rollback', 'status' => 'ACTIVE', 'age' => 1]);
        throw new RuntimeException('rollback');
    });
} catch (\Nova\Database\DatabaseException) {
}
$assert(!$db->table('users')->where('name', 'Rollback')->exists(), 'Transaction rollback failed.');

$db->begin();
$db->insert('users', ['name' => 'Manual', 'status' => 'ACTIVE', 'age' => 1]);
$db->rollback();
$assert(!$db->table('users')->where('name', 'Manual')->exists(), 'Manual rollback failed.');

$pdo = $connection->pdo();
if (method_exists($pdo, 'createFunction')) {
    $pdo->createFunction('sp_add', fn (int $a, int $b): int => $a + $b, 2);
} else {
    @$pdo->sqliteCreateFunction('sp_add', fn (int $a, int $b): int => $a + $b, 2);
}
$procedure = $db->procedure('sp_add', [2, 3]);
$assert((int) ($procedure['first'][0]['result'] ?? 0) === 5, 'Procedure-style routine execution failed.');

try {
    $db->statement('SELECT * FROM users WHERE name = ?', [["not scalar"]]);
    $assert(false, 'Expected DatabaseException for invalid binding.');
} catch (\Nova\Database\DatabaseException) {
}

$assert(is_file($logPath) && str_contains((string) file_get_contents($logPath), 'SELECT COUNT(*)'), 'Query logging failed.');

echo "Database engine smoke test passed." . PHP_EOL;
