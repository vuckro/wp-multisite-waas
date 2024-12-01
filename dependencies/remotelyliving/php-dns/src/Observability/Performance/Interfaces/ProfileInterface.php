<?php

namespace WP_Ultimo\Dependencies\RemotelyLiving\PHPDNS\Observability\Performance\Interfaces;

interface ProfileInterface
{
    public function startTransaction() : void;
    public function endTransaction() : void;
    public function getTransactionName() : string;
    public function getElapsedSeconds() : float;
    public function samplePeakMemoryUsage() : void;
    public function getPeakMemoryUsage() : int;
}
