<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Traits;

use WP_Ultimo\Dependencies\Psr\Log\LoggerInterface;
use WP_Ultimo\Dependencies\Psr\Log\NullLogger;
trait Logger
{
    private ?LoggerInterface $logger = null;
    public function setLogger(LoggerInterface $logger) : void
    {
        $this->logger = $logger;
    }
    protected function getLogger() : LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }
}
