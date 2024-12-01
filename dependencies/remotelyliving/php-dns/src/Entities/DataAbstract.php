<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\Arrayable;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\Serializable;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Exceptions\InvalidArgumentException;
use function count;
use function explode;
use function trim;
abstract class DataAbstract implements Arrayable, Serializable
{
    public abstract function __toString() : string;
    public abstract function toArray() : array;
    public function equals(DataAbstract $dataAbstract) : bool
    {
        return (string) $this === (string) $dataAbstract;
    }
    /**
     * @throws \RemotelyLiving\PHPDNS\Exceptions\InvalidArgumentException
     */
    public static function createFromTypeAndString(DNSRecordType $recordType, string $data) : self
    {
        if ($recordType->isA(DNSRecordType::TYPE_TXT)) {
            return new TXTData(trim($data, '"'));
        }
        if ($recordType->isA(DNSRecordType::TYPE_NS)) {
            return new NSData(new Hostname($data));
        }
        if ($recordType->isA(DNSRecordType::TYPE_CNAME)) {
            return new CNAMEData(new Hostname($data));
        }
        $parsed = self::parseDataToArray($data);
        if ($recordType->isA(DNSRecordType::TYPE_MX)) {
            return new MXData(new Hostname($parsed[1]), (int) $parsed[0]);
        }
        if ($recordType->isA(DNSRecordType::TYPE_SOA)) {
            return new SOAData(new Hostname($parsed[0]), new Hostname($parsed[1]), (int) ($parsed[2] ?? 0), (int) ($parsed[3] ?? 0), (int) ($parsed[4] ?? 0), (int) ($parsed[5] ?? 0), (int) ($parsed[6] ?? 0));
        }
        if ($recordType->isA(DNSRecordType::TYPE_CAA) && count($parsed) === 3) {
            return new CAAData((int) $parsed[0], (string) $parsed[1], $parsed[2]);
        }
        if ($recordType->isA(DNSRecordType::TYPE_SRV)) {
            return new SRVData((int) $parsed[0] ?: 0, (int) $parsed[1] ?: 0, (int) $parsed[2] ?: 0, new Hostname($parsed[3]));
        }
        if ($recordType->isA(DNSRecordType::TYPE_PTR)) {
            return new PTRData(new Hostname($data));
        }
        throw new InvalidArgumentException("{$data} could not be created with type {$recordType}");
    }
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
    private static function parseDataToArray(string $data) : array
    {
        return explode(' ', $data);
    }
}
