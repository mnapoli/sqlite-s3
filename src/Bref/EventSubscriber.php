<?php declare(strict_types=1);

namespace SQLiteS3\Bref;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Listener\BrefEventSubscriber;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * @internal
 */
class EventSubscriber extends BrefEventSubscriber
{
    public function afterInvoke(
        callable | Handler | RequestHandlerInterface $handler,
        mixed $event,
        Context $context,
        mixed $result,
        Throwable | null $error = null,
    ): void {
        // Close all SQLite connections to avoid memory leaks
        ConnectionTracker::closeAll();
    }
}