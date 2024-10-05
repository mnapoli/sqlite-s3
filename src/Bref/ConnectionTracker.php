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
    /** @var WeakMap<PDOSQLiteS3, bool> */
    private static WeakMap $connections;

    public static function trackConnection(PDOSQLiteS3 $connection): void
    {
        if (! isset(self::$connections)) {
            // @phpstan-ignore-next-line
            self::$connections = new WeakMap;
        }

        self::$connections[$connection] = true;
    }

    public static function closeAll(): void
    {
        if (isset(self::$connections)) {
            foreach (self::$connections as $connection => $_) {
                $connection->close();
            }
        }

        // @phpstan-ignore-next-line
        self::$connections = new WeakMap;
    }
}
