<?php

namespace WP_Ultimo\Dependencies\Mpdf\File;

class LocalContentLoader implements \WP_Ultimo\Dependencies\Mpdf\File\LocalContentLoaderInterface
{
    public function load($path)
    {
        return \file_get_contents($path);
    }
}
