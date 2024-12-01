<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Subscribers;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events\DNSQueried;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events\DNSQueryFailed;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events\DNSQueryProfiled;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events\ObservableEventAbstract;
use SplFileObject;
use WP_Ultimo\Dependencies\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function json_encode;
final class STDIOSubscriber implements EventSubscriberInterface
{
    private SplFileObject $STDOUT;
    private SplFileObject $STDERR;
    public function __construct(SplFileObject $stdOut, SplFileObject $stdErr)
    {
        $this->STDOUT = $stdOut;
        $this->STDERR = $stdErr;
    }
    public static function getSubscribedEvents()
    {
        return [DNSQueryFailed::getName() => 'onDNSQueryFailed', DNSQueried::getName() => 'onDNSQueried', DNSQueryProfiled::getName() => 'onDNSQueryProfiled'];
    }
    public function onDNSQueryFailed(ObservableEventAbstract $event) : void
    {
        $this->STDERR->fwrite(json_encode($event, \JSON_PRETTY_PRINT) . \PHP_EOL);
    }
    public function onDNSQueried(ObservableEventAbstract $event) : void
    {
        $this->STDOUT->fwrite(json_encode($event, \JSON_PRETTY_PRINT) . \PHP_EOL);
    }
    public function onDNSQueryProfiled(ObservableEventAbstract $event) : void
    {
        $this->STDOUT->fwrite(json_encode($event, \JSON_PRETTY_PRINT) . \PHP_EOL);
    }
}
