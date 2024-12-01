<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Events;

use JsonSerializable;
use WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Entities\Interfaces\Arrayable;
use WP_Ultimo\Dependencies\Symfony\Component\EventDispatcher\GenericEvent;
abstract class ObservableEventAbstract extends GenericEvent implements JsonSerializable, Arrayable
{
    public static abstract function getName() : string;
    public function jsonSerialize() : array
    {
        return [$this::getName() => $this->toArray()];
    }
}
