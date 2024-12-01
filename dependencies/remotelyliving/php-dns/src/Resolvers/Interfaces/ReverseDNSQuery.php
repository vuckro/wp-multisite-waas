<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Interfaces;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Hostname;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\IPAddress;
interface ReverseDNSQuery
{
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\ReverseLookupFailure
     */
    public function getHostnameByAddress(string $IPAddress) : Hostname;
}
