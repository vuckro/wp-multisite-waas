<?php

namespace WP_Ultimo\Dependencies\Mpdf\File;

interface LocalContentLoaderInterface
{
    /**
     * @return string|null
     */
    public function load($path);
}
