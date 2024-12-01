<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Services;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Exceptions\ReverseLookupFailure;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Services\Interfaces\LocalSystemDNS as LocalSystemDNSInterface;
use function dns_get_record;
use function gethostbyaddr;
final class LocalSystemDNS implements LocalSystemDNSInterface
{
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getRecord(string $hostname, int $type) : array
    {
        $results = @dns_get_record($hostname, $type);
        // this is untestable without creating a system networking failure
        // @codeCoverageIgnoreStart
        if ($results === \false) {
            throw new QueryFailure();
        }
        // @codeCoverageIgnoreEnd
        return $results;
    }
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\ReverseLookupFailure
     */
    public function getHostnameByAddress(string $IPAddress) : string
    {
        $hostname = @gethostbyaddr($IPAddress);
        if ($hostname === $IPAddress || $hostname === \false) {
            throw new ReverseLookupFailure();
        }
        return $hostname;
    }
}
