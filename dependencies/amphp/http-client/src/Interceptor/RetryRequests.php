<?php

namespace WP_Ultimo\Dependencies\Amp\Http\Client\Interceptor;

use WP_Ultimo\Dependencies\Amp\CancellationToken;
use WP_Ultimo\Dependencies\Amp\Http\Client\ApplicationInterceptor;
use WP_Ultimo\Dependencies\Amp\Http\Client\Connection\Http2ConnectionException;
use WP_Ultimo\Dependencies\Amp\Http\Client\Connection\UnprocessedRequestException;
use WP_Ultimo\Dependencies\Amp\Http\Client\DelegateHttpClient;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidCloning;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidSerialization;
use WP_Ultimo\Dependencies\Amp\Http\Client\Request;
use WP_Ultimo\Dependencies\Amp\Http\Client\SocketException;
use WP_Ultimo\Dependencies\Amp\Promise;
use function WP_Ultimo\Dependencies\Amp\call;
final class RetryRequests implements ApplicationInterceptor
{
    use ForbidCloning;
    use ForbidSerialization;
    /** @var int */
    private $retryLimit;
    public function __construct(int $retryLimit)
    {
        $this->retryLimit = $retryLimit;
    }
    public function request(Request $request, CancellationToken $cancellation, DelegateHttpClient $httpClient) : Promise
    {
        return call(function () use($request, $cancellation, $httpClient) {
            $attempt = 1;
            do {
                try {
                    return (yield $httpClient->request(clone $request, $cancellation));
                } catch (UnprocessedRequestException $exception) {
                    // Request was deemed retryable by connection, so carry on.
                } catch (SocketException|Http2ConnectionException $exception) {
                    if (!$request->isIdempotent()) {
                        throw $exception;
                    }
                    // Request can safely be retried.
                }
            } while ($attempt++ <= $this->retryLimit);
            throw $exception;
        });
    }
}
