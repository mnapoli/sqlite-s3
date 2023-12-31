<?php declare(strict_types=1);

namespace SQLiteS3;

use AsyncAws\Core\Configuration;
use PDO;
use SQLiteS3\Bref\ConnectionTracker;

class PDOSQLiteS3 extends PDO
{
    private readonly DbSynchronizer $dbSynchronizer;

    /**
     * @param Configuration|array<Configuration::OPTION_*, string|null> $s3ClientConfig
     */
    public function __construct(string $bucket, string $key, array|Configuration $s3ClientConfig = [])
    {
        $this->dbSynchronizer = new DbSynchronizer($bucket, $key, $s3ClientConfig);

        $dbFileName = $this->dbSynchronizer->open();

        parent::__construct('sqlite:' . $dbFileName);

        ConnectionTracker::trackConnection($this);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        $this->dbSynchronizer->close();
    }
}
