<?php declare(strict_types=1);

namespace SQLiteS3;

use AsyncAws\Core\Configuration;
use PDO;

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
    }

    public function __destruct()
    {
        $this->dbSynchronizer->close();
    }
}
