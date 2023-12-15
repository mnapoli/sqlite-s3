# Serverless dev database: SQLite backed by S3

## Why?

A "serverless" SQS database:

- For development and testing purposes
- Costs $0
- As simple to use as possible
- Ideal for [serverless environments](https://bref.sh/) like AWS Lambda

**Not for production use-cases**. It does not handle concurrent updates (in which case some data might be lost) and performances are not production-grade.

## How?

The SQLite database (a file) is stored on S3. The PHP class will transparently download the file locally on every request, and upload it back at the end.

If two concurrent requests download the database file, update it (separately), and upload it back, then the last to upload the modified file will overwrite the changes of the other request.

That is why this solution is best for testing scenarios (e.g. testing a fully deployed application, where there is one test running at a time). It could also work for development environments with only one active user at a time, where an extra 50ms-100ms per request is acceptable.

## Setup

You will need an AWS S3 bucket (where the database will be stored). Install the package with Composer:

```sh
composer require mnapoli/sqlite-s3
```

## Usage

Instead of:

```php
$db = new PDO('sqlite:test-db.sqlite');
$db->exec('SELECT * FROM my-table');
```

Use:

```php
$db = new PDOSQLiteS3('the-s3-bucket-name', 'a-file-name.sqlite');
$db->exec('SELECT * FROM my-table');
```

The S3 bucket must exist, but **the SQLite database file will automatically be created** if it doesn't.

The database will be uploaded back to S3 when the `$db` instead is destroyed (i.e. when the PDO connection is closed).

### Configuration

If needed, set the AWS region:

```php
$db = new PDOSQLiteS3('the-s3-bucket-name', 'a-file-name.sqlite', [
    'region' => 'us-east-1',
]);
```

The AWS credentials will automatically be picked up by the AWS SDK. The [Async-AWS](https://async-aws.com/) library is used under the hood, check out [its documentation](https://async-aws.com/authentication/).

### Without PDO

If you are using the [`SQLite3` class](https://www.php.net/manual/en/class.sqlite3.php) directly, replace it with the `SQLiteS3` class:

```php
$db = new SQLiteS3('the-s3-bucket-name', 'a-file-name.sqlite');
```
