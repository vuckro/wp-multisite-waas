<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Interfaces;

interface Resolver extends DNSQuery
{
    public function getName() : string;
}
