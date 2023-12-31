<?php declare(strict_types=1);

namespace SQLiteS3\Bref;

use SQLiteS3\PDOSQLiteS3;
use WeakMap;

/**
 * @internal
 */
abstract class ConnectionTracker
{
    // We use a WeakMap to track connections so that we don't prevent them from being garbage collected on their own
    /** @var array<PDOSQLiteS3, bool> */
    private static WeakMap $connections;

    public static function trackConnection(PDOSQLiteS3 $connection): void
    {
        if (! isset(self::$connections)) {
            self::$connections = new WeakMap;
        }

        self::$connections[$connection] = true;
    }

    public static function closeAll(): void
    {
        foreach (self::$connections as $connection => $_) {
            $connection->close();
        }

        self::$connections = new WeakMap;
    }
}