<?php

namespace WP_Ultimo\Dependencies\Mpdf\Container;

interface ContainerInterface
{
    public function get($id);
    public function has($id);
}
