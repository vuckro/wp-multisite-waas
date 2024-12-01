<?php

namespace WP_Ultimo\Dependencies\Amp\Http\Client\Connection;

use WP_Ultimo\Dependencies\Amp\CancellationToken;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidCloning;
use WP_Ultimo\Dependencies\Amp\Http\Client\Internal\ForbidSerialization;
use WP_Ultimo\Dependencies\Amp\Http\Client\Request;
use WP_Ultimo\Dependencies\Amp\Http\Client\Response;
use WP_Ultimo\Dependencies\Amp\Promise;
use WP_Ultimo\Dependencies\Amp\Sync\KeyedSemaphore;
use WP_Ultimo\Dependencies\Amp\Sync\Lock;
use function WP_Ultimo\Dependencies\Amp\call;
use function WP_Ultimo\Dependencies\Amp\coroutine;
final class StreamLimitingPool implements ConnectionPool
{
    use ForbidCloning;
    use ForbidSerialization;
    public static function byHost(ConnectionPool $delegate, KeyedSemaphore $semaphore) : self
    {
        return new self($delegate, $semaphore, static function (Request $request) {
            return $request->getUri()->getHost();
        });
    }
    public static function byStaticKey(ConnectionPool $delegate, KeyedSemaphore $semaphore, string $key = '') : self
    {
        return new self($delegate, $semaphore, static function () use($key) {
            return $key;
        });
    }
    public static function byCustomKey(ConnectionPool $delegate, KeyedSemaphore $semaphore, callable $requestToKeyMapper) : self
    {
        return new self($delegate, $semaphore, $requestToKeyMapper);
    }
    /** @var ConnectionPool */
    private $delegate;
    /** @var KeyedSemaphore */
    private $semaphore;
    /** @var callable */
    private $requestToKeyMapper;
    private function __construct(ConnectionPool $delegate, KeyedSemaphore $semaphore, callable $requestToKeyMapper)
    {
        $this->delegate = $delegate;
        $this->semaphore = $semaphore;
        $this->requestToKeyMapper = $requestToKeyMapper;
    }
    public function getStream(Request $request, CancellationToken $cancellation) : Promise
    {
        return call(function () use($request, $cancellation) {
            /** @var Lock $lock */
            $lock = (yield $this->semaphore->acquire(($this->requestToKeyMapper)($request)));
            /** @var Stream $stream */
            $stream = (yield $this->delegate->getStream($request, $cancellation));
            return HttpStream::fromStream($stream, coroutine(static function (Request $request, CancellationToken $cancellationToken) use($stream, $lock) {
                try {
                    /** @var Response $response */
                    $response = (yield $stream->request($request, $cancellationToken));
                    // await response being completely received
                    $response->getTrailers()->onResolve(static function () use($lock) {
                        $lock->release();
                    });
                } catch (\Throwable $e) {
                    $lock->release();
                    throw $e;
                }
                return $response;
            }), static function () use($lock) {
                $lock->release();
            });
        });
    }
}
