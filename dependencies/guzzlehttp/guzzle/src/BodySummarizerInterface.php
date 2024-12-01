<?php

namespace WP_Ultimo\Dependencies\GuzzleHttp;

use WP_Ultimo\Dependencies\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
