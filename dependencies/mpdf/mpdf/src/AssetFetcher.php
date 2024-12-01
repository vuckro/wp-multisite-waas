<?php

namespace WP_Ultimo\Dependencies\Mpdf;

use WP_Ultimo\Dependencies\Mpdf\File\LocalContentLoaderInterface;
use WP_Ultimo\Dependencies\Mpdf\File\StreamWrapperChecker;
use WP_Ultimo\Dependencies\Mpdf\Http\ClientInterface;
use WP_Ultimo\Dependencies\Mpdf\Log\Context as LogContext;
use WP_Ultimo\Dependencies\Mpdf\PsrHttpMessageShim\Request;
use WP_Ultimo\Dependencies\Mpdf\PsrLogAwareTrait\PsrLogAwareTrait;
use WP_Ultimo\Dependencies\Psr\Log\LoggerInterface;
class AssetFetcher implements \WP_Ultimo\Dependencies\Psr\Log\LoggerAwareInterface
{
    use PsrLogAwareTrait;
    private $mpdf;
    private $contentLoader;
    private $http;
    public function __construct(Mpdf $mpdf, LocalContentLoaderInterface $contentLoader, ClientInterface $http, LoggerInterface $logger)
    {
        $this->mpdf = $mpdf;
        $this->contentLoader = $contentLoader;
        $this->http = $http;
        $this->logger = $logger;
    }
    public function fetchDataFromPath($path, $originalSrc = null)
    {
        /**
         * Prevents insecure PHP object injection through phar:// wrapper
         * @see https://github.com/mpdf/mpdf/issues/949
         * @see https://github.com/mpdf/mpdf/issues/1381
         */
        $wrapperChecker = new StreamWrapperChecker($this->mpdf);
        if ($wrapperChecker->hasBlacklistedStreamWrapper($path)) {
            throw new \WP_Ultimo\Dependencies\Mpdf\Exception\AssetFetchingException('File contains an invalid stream. Only ' . \implode(', ', $wrapperChecker->getWhitelistedStreamWrappers()) . ' streams are allowed.');
        }
        if ($originalSrc && $wrapperChecker->hasBlacklistedStreamWrapper($originalSrc)) {
            throw new \WP_Ultimo\Dependencies\Mpdf\Exception\AssetFetchingException('File contains an invalid stream. Only ' . \implode(', ', $wrapperChecker->getWhitelistedStreamWrappers()) . ' streams are allowed.');
        }
        $this->mpdf->GetFullPath($path);
        return $this->isPathLocal($path) || $originalSrc !== null && $this->isPathLocal($originalSrc) ? $this->fetchLocalContent($path, $originalSrc) : $this->fetchRemoteContent($path);
    }
    public function fetchLocalContent($path, $originalSrc)
    {
        $data = '';
        if ($originalSrc && $this->mpdf->basepathIsLocal && ($check = @\fopen($originalSrc, 'rb'))) {
            \fclose($check);
            $path = $originalSrc;
            $this->logger->debug(\sprintf('Fetching content of file "%s" with local basepath', $path), ['context' => LogContext::REMOTE_CONTENT]);
            return $this->contentLoader->load($path);
        }
        if ($path && ($check = @\fopen($path, 'rb'))) {
            \fclose($check);
            $this->logger->debug(\sprintf('Fetching content of file "%s" with non-local basepath', $path), ['context' => LogContext::REMOTE_CONTENT]);
            return $this->contentLoader->load($path);
        }
        return $data;
    }
    public function fetchRemoteContent($path)
    {
        $data = '';
        try {
            $this->logger->debug(\sprintf('Fetching remote content of file "%s"', $path), ['context' => LogContext::REMOTE_CONTENT]);
            /** @var \Mpdf\PsrHttpMessageShim\Response $response */
            $response = $this->http->sendRequest(new Request('GET', $path));
            if ($response->getStatusCode() !== 200) {
                $message = \sprintf('Non-OK HTTP response "%s" on fetching remote content "%s" because of an error', $response->getStatusCode(), $path);
                if ($this->mpdf->debug) {
                    throw new \WP_Ultimo\Dependencies\Mpdf\MpdfException($message);
                }
                $this->logger->info($message);
                return $data;
            }
            $data = $response->getBody()->getContents();
        } catch (\InvalidArgumentException $e) {
            $message = \sprintf('Unable to fetch remote content "%s" because of an error "%s"', $path, $e->getMessage());
            if ($this->mpdf->debug) {
                throw new \WP_Ultimo\Dependencies\Mpdf\MpdfException($message, 0, \E_ERROR, null, null, $e);
            }
            $this->logger->warning($message);
        }
        return $data;
    }
    public function isPathLocal($path)
    {
        return \str_starts_with($path, 'file://') || \strpos($path, '://') === \false;
        // @todo More robust implementation
    }
}
