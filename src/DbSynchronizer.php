<?php declare(strict_types=1);

namespace SQLiteS3;

use AsyncAws\Core\Configuration;
use AsyncAws\S3\Exception\NoSuchKeyException;
use AsyncAws\SimpleS3\SimpleS3Client;
use Bref\Logger\StderrLogger;
use Psr\Log\LogLevel;
use RuntimeException;

/**
 * @internal
 */
class DbSynchronizer
{
    private readonly SimpleS3Client $s3;
    private string | null $dbFileName = null;
    private readonly StderrLogger $logger;

    /**
     * @param Configuration|array<Configuration::OPTION_*, string|null> $s3ClientConfig
     */
    public function __construct(
        private readonly string $bucket,
        private readonly string $key,
        array|Configuration $s3ClientConfig = [],
    ) {
        $this->logger = new StderrLogger(LogLevel::INFO);
        $this->s3 = new SimpleS3Client($s3ClientConfig);
    }

    /**
     * @return string The path to the DB file name
     */
    public function open(): string
    {
        $this->logger->info('Downloading and opening the SQLite database');

        try {
            $contentAsResource = $this->s3->download($this->bucket, $this->key)->getContentAsResource();
        } catch (NoSuchKeyException) {
            // The file does not exist yet, create an empty one
            $contentAsResource = fopen('php://memory', 'rb');
        }

        $this->dbFileName = tempnam(sys_get_temp_dir(), 'db.sqlite');
        if ($this->dbFileName === false) {
            throw new RuntimeException('Could not create temporary file');
        }
        $fileResource = fopen($this->dbFileName, 'wb');

        $success = stream_copy_to_stream($contentAsResource, $fileResource);
        if ($success === false) {
            throw new RuntimeException('Could not dump S3 file to temporary file');
        }
        if (! fclose($fileResource)) {
            throw new RuntimeException('Could not close temporary file');
        }

        return $this->dbFileName;
    }

    public function close(): void
    {
        if (! $this->dbFileName) {
            return;
        }

        $this->logger->info('Closing and uploading the SQLite database');

        // Upload back to S3
        $contentAsResource = fopen($this->dbFileName, 'rb');
        $this->s3->upload($this->bucket, $this->key, $contentAsResource);
        fclose($contentAsResource);

        unlink($this->dbFileName);

        $this->dbFileName = null;
    }
}
