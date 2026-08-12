<?php

namespace App\Services;

use Exception;
use Throwable;

class GitHostingProviderException extends Exception
{
    private string $provider;

    function __construct(string $message, string $provider, int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->provider = $provider;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}