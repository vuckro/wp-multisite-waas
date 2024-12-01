<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordCollection;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordType;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Hostname;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\IPAddress;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Mappers\LocalSystem as LocalMapper;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Interfaces\ReverseDNSQuery;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Services\Interfaces\LocalSystemDNS;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Services\LocalSystemDNS as LocalDNSService;
final class LocalSystem extends ResolverAbstract implements ReverseDNSQuery
{
    private LocalSystemDNS $systemDNS;
    private LocalMapper $mapper;
    public function __construct(LocalSystemDNS $systemDNS = null, LocalMapper $mapper = null)
    {
        $this->systemDNS = $systemDNS ?? new LocalDNSService();
        $this->mapper = $mapper ?? new LocalMapper();
    }
    public function getHostnameByAddress(string $IPAddress) : Hostname
    {
        $result = $this->systemDNS->getHostnameByAddress((string) new IPAddress($IPAddress));
        return Hostname::createFromString($result);
    }
    protected function doQuery(Hostname $hostname, DNSRecordType $recordType) : DNSRecordCollection
    {
        $results = $this->systemDNS->getRecord(
            $hostname->getHostnameWithoutTrailingDot(),
            // dns_get_record doesn't like trailing dot as much!
            $this->mapper->getTypeCodeFromType($recordType)
        );
        $collection = new DNSRecordCollection();
        foreach ($results as $result) {
            $collection[] = $this->mapper->mapFields($result)->toDNSRecord();
        }
        return $collection;
    }
}
