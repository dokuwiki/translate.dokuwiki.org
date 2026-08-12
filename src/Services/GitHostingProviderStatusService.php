<?php

namespace App\Services;


/**
 * Parent Class for Git Hosting Status Services
 */
abstract class GitHostingProviderStatusService
{
    /**
     * URL to Hosting Provider's status site.
     */
    protected const STATUS_URL = '';

    protected ?bool $status = null;

    /**
     * Check if Hosting Provider is functional.
     *
     * @return bool true if status is good, otherwise false
     */
    public function isFunctional(): bool
    {
        if ($this->status === null) {
            $json = file_get_contents($this::STATUS_URL);
            $this->status = $this->checkResponse($json);
        }
        return $this->status;
    }

    /**
     * Returns true if response status of API Requests is good, otherwise false
     *
     * @param string|false $content
     * @return bool
     */
    abstract protected function checkResponse($content): bool;

}
