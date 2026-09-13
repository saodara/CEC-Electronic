<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use Throwable;

/**
 * Neon's compute is reachable over the public internet, so establishing a
 * fresh PDO connection is more exposed to brief DNS/network blips than a
 * local Postgres instance would be (see: "could not translate host name ...
 * Temporary failure in name resolution"). Laravel's base Connector already
 * retries once, immediately, on that class of error — this adds a couple of
 * extra attempts with a short backoff so a blip lasting longer than an
 * instant doesn't surface as a 500 to the user.
 */
class RetryingPostgresConnector extends PostgresConnector
{
    private const MAX_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 300;

    public function connect(array $config)
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return parent::connect($config);
            } catch (Throwable $e) {
                if ($attempt === self::MAX_ATTEMPTS || ! $this->causedByLostConnection($e)) {
                    throw $e;
                }

                usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            }
        }
    }
}
