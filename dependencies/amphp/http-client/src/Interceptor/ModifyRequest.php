<?php

namespace WP_Ultimo\Dependencies\Amp\Http\Client\Interceptor;

use WP_Ultimo\Dependencies\Amp\CancellationToken;
use WP_Ultimo\Dependencies\Amp\Http\Client\ApplicationInterceptor;
use WP_Ultimo\Dependencies\Amp\Http\Client\Connection\Stream;
use WP_Ultimo\Dependencies\Amp\Http\Client\DelegateHttpClient;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidCloning;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidSerialization;
use WP_Ultimo\Dependencies\Amp\Http\Client\NetworkInterceptor;
use WP_Ultimo\Dependencies\Amp\Http\Client\Request;
use WP_Ultimo\Dependencies\Amp\Http\Client\Response;
use WP_Ultimo\Dependencies\Amp\Promise;
use function WP_Ultimo\Dependencies\Amp\call;
class ModifyRequest implements NetworkInterceptor, ApplicationInterceptor
{
    use ForbidCloning;
    use ForbidSerialization;
    /** @var callable(Request):(\Generator<mixed, mixed, mixed, Promise<Request|null>|Request|null>|Promise<Request|null>|Request|null) */
    private $mapper;
    /**
     * @psalm-param callable(Request):(\Generator<mixed, mixed, mixed, Promise<Request|null>|Request|null>|Promise<Request|null>|Request|null) $mapper
     */
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }
    /**
     * @param Request           $request
     * @param CancellationToken $cancellation
     * @param Stream            $stream
     *
     * @return Promise<Response>
     */
    public final function requestViaNetwork(Request $request, CancellationToken $cancellation, Stream $stream) : Promise
    {
        return call(function () use($request, $cancellation, $stream) {
            $mappedRequest = (yield call($this->mapper, $request));
            \assert($mappedRequest instanceof Request || $mappedRequest === null);
            return (yield $stream->request($mappedRequest ?? $request, $cancellation));
        });
    }
    public function request(Request $request, CancellationToken $cancellation, DelegateHttpClient $httpClient) : Promise
    {
        return call(function () use($request, $cancellation, $httpClient) {
            $mappedRequest = (yield call($this->mapper, $request));
            \assert($mappedRequest instanceof Request || $mappedRequest === null);
            return $httpClient->request($mappedRequest ?? $request, $cancellation);
        });
    }
}
