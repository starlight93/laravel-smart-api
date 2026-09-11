<?php
/**
 * Self-check for EditorFunc::schemaManager() — the shim replacing
 * DB::getDoctrineSchemaManager() removed in Laravel 11+.
 *
 * Run against any reachable DB:
 *   SHIM_DSN='pgsql;127.0.0.1;5432;mydb;user;pass' \
 *   php artisan tinker --execute="require 'packages/api/tests/schema_shim_check.php';"
 *
 * Defaults to the app's own default connection when SHIM_DSN is unset.
 */

use Illuminate\Support\Facades\DB;
use Starlight93\LaravelSmartApi\Helpers\EditorFunc as Ed;

if ($dsn = getenv('SHIM_DSN')) {
    [$driver, $host, $port, $database, $username, $password] = array_pad(explode(';', $dsn), 6, null);
    config(['database.connections.shimcheck' => compact('driver', 'host', 'port', 'database', 'username', 'password')
        + ['charset' => 'utf8', 'search_path' => 'public', 'prefix' => '']]);
    $conn = DB::connection('shimcheck');
} else {
    $conn = DB::connection();
}

$conn->statement('drop table if exists ed_shim_demo');
$conn->statement('create table ed_shim_demo (id serial primary key, name varchar(50) not null, email varchar(80) unique)');

try {
    $sm = Ed::schemaManager($conn);

    assert(in_array('ed_shim_demo', $sm->listTableNames()), 'listTableNames() missing ed_shim_demo');

    $table = $sm->listTableDetails('ed_shim_demo');
    assert(count($table->getColumns()) === 3, 'expected 3 columns, got ' . count($table->getColumns()));
    assert($table->getColumn('name')->getNotnull() === true, 'name should be NOT NULL');
    assert($table->getColumn('name')->getType()->getName() === 'string', 'name should map to string');
    assert(count($table->getIndexes()) >= 2, 'expected primary + unique index');

    assert(count(Ed::serverSchemaManager($conn)->listDatabases()) > 0, 'serverSchemaManager listed no databases');

    echo "PASS schema shim\n";
} finally {
    $conn->statement('drop table if exists ed_shim_demo');
}
