<?php

namespace KitLoong\MigrationsGenerator\Tests\Feature\MySQL57;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DBConnectionTest extends MySQL57TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql8');
        $app['config']->set('database.connections.mysql8', [
            'driver'         => 'mysql',
            'url'            => null,
            'host'           => env('MYSQL8_HOST'),
            'port'           => env('MYSQL8_PORT'),
            'database'       => env('MYSQL8_DATABASE'),
            'username'       => env('MYSQL8_USERNAME'),
            'password'       => env('MYSQL8_PASSWORD'),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_general_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ]);
    }

    public function tearDown(): void
    {
        // Clean "migrations" table after test.
        Schema::connection('mysql8')->dropIfExists('migrations');

        // Switch back to mysql57, to drop mysql57 tables in tearDown.
        DB::setDefaultConnection('mysql57');

        parent::tearDown();
    }

    public function testDBConnection()
    {
        $migrateTemplates = function () {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function () {
            // Needed for Laravel 6 and below.
            DB::setDefaultConnection('mysql8');

            $this->artisan(
                'migrate:generate',
                [
                    '--connection' => 'mysql57',
                    '--path'       => $this->getStorageMigrationsPath(),
                ]
            )
                ->expectsQuestion('Do you want to log these migrations in the migrations table?', true)
                ->expectsQuestion(
                    'Log into current connection: mysql57? [Y = mysql57, n = mysql8 (default connection)]',
                    true
                )
                ->expectsQuestion(
                    'Next Batch Number is: 1. We recommend using Batch Number 0 so that it becomes the "first" migration. [Default: 0]',
                    '0'
                );

            $totalMigrations = count(File::allFiles($this->getStorageMigrationsPath()));

            $this->assertSame($totalMigrations, DB::connection('mysql57')->table('migrations')->count());
        };

        $this->verify($migrateTemplates, $generateMigrations);

        $this->assertStringContainsString(
            'Schema::connection',
            File::files($this->getStorageMigrationsPath())[0]->getContents()
        );
    }

    public function testLogMigrationToAnotherSource()
    {
        $this->migrateGeneral('mysql57');

        // Needed for Laravel 6 and below.
        DB::setDefaultConnection('mysql8');

        $this->artisan(
            'migrate:generate',
            [
                '--connection' => 'mysql57',
                '--path'       => $this->getStorageMigrationsPath(),
            ]
        )
            ->expectsQuestion('Do you want to log these migrations in the migrations table?', true)
            ->expectsQuestion(
                'Log into current connection: mysql57? [Y = mysql57, n = mysql8 (default connection)]',
                false
            )
            ->expectsQuestion(
                'Next Batch Number is: 1. We recommend using Batch Number 0 so that it becomes the "first" migration. [Default: 0]',
                '0'
            );

        $totalMigrations = count(File::allFiles($this->getStorageMigrationsPath()));

        $this->assertSame($totalMigrations, DB::connection('mysql8')->table('migrations')->count());
    }

    private function verify(callable $migrateTemplates, callable $generateMigrations)
    {
        $migrateTemplates();

        DB::connection('mysql57')->table('migrations')->truncate();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $generateMigrations();

        $this->assertMigrations();

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        DB::connection('mysql57')->table('migrations')->truncate();
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }
}
