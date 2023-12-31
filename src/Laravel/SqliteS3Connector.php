<?php declare(strict_types=1);

namespace SQLiteS3\Laravel;

use Illuminate\Database\Connectors\SQLiteConnector;
use PDO;
use RuntimeException;
use SQLiteS3\PDOSQLiteS3;

class SqliteS3Connector extends SQLiteConnector
{
    /**
     * @param array<string, mixed> $config
     *
     * @see \Illuminate\Database\Connectors\SQLiteConnector::connect()
     */
    public function connect(array $config): PDO
    {
        $options = $this->getOptions($config);

        if (str_starts_with($config['database'] ?? '', 's3://')) {
            return $this->createConnection("sqlite-s3:{$config['database']}", $config, $options);
        }

        return parent::connect($config);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createPdoConnection($dsn, $username, $password, $options): PDO
    {
        if (str_starts_with($dsn, 'sqlite-s3:')) {
            $success = preg_match('/s3:\/\/([^\/]+)\/(.*)/', $dsn, $matches);
            if (! $success) {
                throw new RuntimeException('Could not parse DSN: ' . $dsn);
            }
            [$_, $bucket, $key] = $matches;
            return new PDOSQLiteS3($bucket, $key);
        }

        return parent::createPdoConnection($dsn, $username, $password, $options);
    }
}
