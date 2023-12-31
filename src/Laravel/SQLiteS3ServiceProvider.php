<?php declare(strict_types=1);

namespace SQLiteS3\Laravel;

use Illuminate\Support\ServiceProvider;

class SQLiteS3ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override the default sqlite connector with our own
        $this->app->bind('db.connector.sqlite', SqliteS3Connector::class);
    }
}
