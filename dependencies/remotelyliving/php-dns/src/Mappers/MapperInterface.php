<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Mappers;

use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\DNSRecordInterface;
interface MapperInterface
{
    public function mapFields(array $fields) : MapperInterface;
    public function toDNSRecord() : DNSRecordInterface;
}
