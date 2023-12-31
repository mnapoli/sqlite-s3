<?php declare(strict_types=1);

namespace SQLiteS3;

use AsyncAws\Core\Configuration;
use RuntimeException;
use SQLite3;

class SQLiteS3 extends SQLite3
{
    private readonly DbSynchronizer $dbSynchronizer;

    /**
     * @param Configuration|array<Configuration::OPTION_*, string|null> $s3ClientConfig
     */
    public function __construct(string $bucket, string $key, array|Configuration $s3ClientConfig = [])
    {
        $this->dbSynchronizer = new DbSynchronizer($bucket, $key, $s3ClientConfig);

        $dbFileName = $this->dbSynchronizer->open();

        parent::__construct($dbFileName);
    }

    public function close(): bool
    {
        $success = parent::close();
        if ($success === false) {
            throw new RuntimeException('Could not close SQLite3 database');
        }

        $this->dbSynchronizer->close();

        return $success;
    }

    public function __destruct()
    {
        $this->close();
    }
}
