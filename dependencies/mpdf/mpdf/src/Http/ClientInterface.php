<?php

namespace WP_Ultimo\Dependencies\Mpdf\Http;

use WP_Ultimo\Dependencies\Psr\Http\Message\RequestInterface;
interface ClientInterface
{
    public function sendRequest(RequestInterface $request);
}
