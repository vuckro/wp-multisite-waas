<?php

namespace WP_Ultimo\Dependencies\Mpdf\PsrLogAwareTrait;

use WP_Ultimo\Dependencies\Psr\Log\LoggerInterface;
trait PsrLogAwareTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
