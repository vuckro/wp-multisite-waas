<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Resolvers\Interfaces;

use WP_Ultimo\Dependencies\Psr\Log\LoggerAwareInterface;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Interfaces\Observable;
interface ObservableResolver extends Resolver, Observable, LoggerAwareInterface
{
}
