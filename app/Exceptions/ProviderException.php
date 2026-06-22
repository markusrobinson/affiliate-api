<?php

namespace App\Exceptions;

use App\Enums\AffiliateProvider;
use RuntimeException;
use Throwable;

class ProviderException extends RuntimeException
{
    public function __construct(
        public readonly AffiliateProvider $provider,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
