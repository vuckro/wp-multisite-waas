<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Interfaces;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecord;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordCollection;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\DNSRecordInterface;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\DNSRecordType;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Hostname;
interface DNSQuery
{
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getARecords(string $hostname) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getAAAARecords(string $hostname) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getCNAMERecords(string $hostname) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getTXTRecords(string $hostname) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getMXRecords(string $hostname) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function getRecords(string $hostname, string $recordType = null) : DNSRecordCollection;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function recordTypeExists(string $hostname, string $recordType) : bool;
    /**
     * @throws \RemotelyLiving\PHPDNS\Resolvers\Exceptions\QueryFailure
     */
    public function hasRecord(DNSRecordInterface $record) : bool;
}
