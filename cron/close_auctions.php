<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\AuctionService;

try {
    $count = (new AuctionService())->closeExpired();
    fwrite(STDOUT, sprintf("[%s] Closed %d auction(s).\n", date(DATE_ATOM), $count));
} catch (Throwable $exception) {
    fwrite(STDERR, sprintf("[%s] %s\n", date(DATE_ATOM), $exception->getMessage()));
    exit(1);
}
