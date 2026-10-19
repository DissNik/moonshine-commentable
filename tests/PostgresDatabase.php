<?php

declare(strict_types=1);

namespace DissNik\MoonShineCommentable\Tests;

use PDO;
use RuntimeException;

final class PostgresDatabase
{
    private static ?string $name = null;

    private static ?PDO $admin = null;

    public static function create(): void
    {
        if (self::$name !== null) {
            return;
        }
        $admin = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=postgres', 'domo_testing', getenv('DOMO_TEST_PG_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $role = $admin->query('SELECT current_user, rolsuper, rolcreaterole FROM pg_roles WHERE rolname = current_user')->fetch(PDO::FETCH_ASSOC);
        if ($role['current_user'] !== 'domo_testing' || $role['rolsuper'] || $role['rolcreaterole']) {
            throw new RuntimeException('Tests require the restricted domo_testing PostgreSQL role.');
        }
        $name = 'domo_test_commentable_'.bin2hex(random_bytes(8));
        $admin->exec('CREATE DATABASE "'.$name.'" OWNER domo_testing');
        self::$admin = $admin;
        self::$name = $name;
        register_shutdown_function(self::drop(...));
    }

    public static function name(): string
    {
        return self::$name ?? throw new RuntimeException('The PHPUnit PostgreSQL extension has not created an isolated database.');
    }

    public static function drop(): void
    {
        if (self::$name === null) {
            return;
        }
        $name = self::$name;
        if (! preg_match('/^domo_test_commentable_[a-f0-9]{16}$/D', $name)) {
            throw new RuntimeException('Refusing to drop a database outside the test namespace.');
        }
        $statement = self::$admin->prepare('SELECT pg_get_userbyid(datdba) FROM pg_database WHERE datname = ?');
        $statement->execute([$name]);
        if ($statement->fetchColumn() !== 'domo_testing') {
            throw new RuntimeException('Refusing to drop a database not owned by domo_testing.');
        }
        self::$admin->exec('DROP DATABASE "'.$name.'"');
        self::$name = null;
        self::$admin = null;
    }
}
