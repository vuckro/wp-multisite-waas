<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Mappers;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\DNSRecordInterface;
abstract class MapperAbstract implements MapperInterface
{
    protected array $fields = [];
    public final function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }
    public function mapFields(array $fields) : MapperInterface
    {
        return new static($fields);
    }
    public abstract function toDNSRecord() : DNSRecordInterface;
}
