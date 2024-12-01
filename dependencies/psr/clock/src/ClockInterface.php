<?php

namespace WP_Ultimo\Dependencies\Psr\Clock;

use DateTimeImmutable;
interface ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object
     */
    public function now() : DateTimeImmutable;
}
