<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Performance\Interfaces\ProfileInterface;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Performance\Profile;
final class DNSQueryProfiled extends ObservableEventAbstract
{
    public const NAME = 'dns.query.profiled';
    private ProfileInterface $profile;
    public function __construct(ProfileInterface $profile)
    {
        parent::__construct();
        $this->profile = $profile;
    }
    public function getProfile() : ProfileInterface
    {
        return $this->profile;
    }
    public static function getName() : string
    {
        return self::NAME;
    }
    public function toArray() : array
    {
        return ['elapsedSeconds' => $this->profile->getElapsedSeconds(), 'transactionName' => $this->profile->getTransactionName(), 'peakMemoryUsage' => $this->profile->getPeakMemoryUsage()];
    }
}
