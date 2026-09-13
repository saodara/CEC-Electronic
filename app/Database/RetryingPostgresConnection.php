<?php

namespace App\Database;

use Closure;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\QueryException;

/**
 * Mirrors RetryingPostgresConnector's backoff, but for a connection that was
 * already open and then dropped mid-request (Laravel's default behavior here
 * is a single, immediate reconnect-and-retry with no delay).
 */
class RetryingPostgresConnection extends PostgresConnection
{
    private const MAX_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 300;

    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if (! $this->causedByLostConnection($e->getPrevious())) {
            throw $e;
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            usleep(self::RETRY_DELAY_MS * 1000 * $attempt);

            try {
                $this->reconnect();

                return $this->runQueryCallback($query, $bindings, $callback);
            } catch (QueryException $retryException) {
                if ($attempt === self::MAX_ATTEMPTS || ! $this->causedByLostConnection($retryException->getPrevious())) {
                    throw $retryException;
                }
            }
        }
    }
}
