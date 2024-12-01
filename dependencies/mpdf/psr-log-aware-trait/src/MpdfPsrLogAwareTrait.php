<?php

namespace WP_Ultimo\Dependencies\Mpdf\PsrLogAwareTrait;

use WP_Ultimo\Dependencies\Psr\Log\LoggerInterface;
trait MpdfPsrLogAwareTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        if (\property_exists($this, 'services') && \is_array($this->services)) {
            foreach ($this->services as $name) {
                if ($this->{$name} && $this->{$name} instanceof \WP_Ultimo\Dependencies\Psr\Log\LoggerAwareInterface) {
                    $this->{$name}->setLogger($logger);
                }
            }
        }
    }
}
