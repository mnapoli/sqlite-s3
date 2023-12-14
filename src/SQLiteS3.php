<?php declare(strict_types=1);

namespace SQLiteS3;

use AsyncAws\Core\Configuration;
use AsyncAws\S3\Exception\NoSuchKeyException;
use AsyncAws\SimpleS3\SimpleS3Client;
use RuntimeException;
use SQLite3;

class SQLiteS3 extends SQLite3
{
    private readonly string $dbFileName;
    private readonly SimpleS3Client $s3;
    private bool $opened = false;

    /**
     * @param Configuration|array<Configuration::OPTION_*, string|null> $s3ClientConfig
     */
    public function __construct(private readonly string $bucket, private readonly string $key, $s3ClientConfig = [])
    {
        $this->s3 = new SimpleS3Client($s3ClientConfig);

        try {
            $contentAsResource = $this->s3->download($bucket, $key)->getContentAsResource();
        } catch (NoSuchKeyException) {
            // The file does not exist yet, create an empty one
            $contentAsResource = fopen('php://memory', 'rb');
        }

        $this->dbFileName = tempnam(sys_get_temp_dir(), 'sqlite');
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

        $this->opened = true;

        parent::__construct($this->dbFileName);
    }

    public function close(): bool
    {
        if (! $this->opened) {
            return true;
        }

        $success = parent::close();
        if ($success === false) {
            throw new RuntimeException('Could not close SQLite3 database');
        }

        // Upload back to S3
        $contentAsResource = fopen($this->dbFileName, 'rb');
        $this->s3->upload($this->bucket, $this->key, $contentAsResource);
        fclose($contentAsResource);

        unlink($this->dbFileName);

        $this->opened = false;

        return $success;
    }

    public function __destruct()
    {
        $this->close();
    }
}
