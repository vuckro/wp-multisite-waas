<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Factories;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Hostname;
use WP_Ultimo\Dependencies\Spatie\Dns\Dns;
class SpatieDNS
{
    public function createResolver(Hostname $domain, Hostname $nameserver = null) : Dns
    {
        return new Dns((string) $domain, (string) $nameserver);
    }
}
