<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Performance;

class ProfileFactory
{
    public function create(string $transactionName) : Profile
    {
        return new Profile($transactionName);
    }
}
