<?php

namespace WP_Ultimo\Dependencies\Amp\Http\Client\Connection;

use WP_Ultimo\Dependencies\Amp\CancellationToken;
use WP_Ultimo\Dependencies\Amp\Http\Client\Request;
use WP_Ultimo\Dependencies\Amp\Promise;
interface ConnectionPool
{
    /**
     * Reserve a stream for a particular request.
     *
     * @param Request           $request
     * @param CancellationToken $cancellation
     *
     * @return Promise<Stream>
     */
    public function getStream(Request $request, CancellationToken $cancellation) : Promise;
}
