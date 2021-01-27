<?php

namespace ZoiloMora\ElasticAPM\Reporter;

use Exception;
use ZoiloMora\ElasticAPM\Events\Common\Service\Agent;
use ZoiloMora\ElasticAPM\Helper\NDJson;
use ZoiloMora\ElasticAPM\Helper\Compressor;

final class ApmServerCurlReporter implements Reporter
{
    const METHOD = 'POST';
    const URI = '/intake/v2/events';
    // See: https://www.php.net/manual/en/function.curl-setopt.php
    // Tell cURL that it should only spend 3 seconds
    // trying to connect to the URL in question.
    const CONNECTION_TIMEOUT = 3;
    // A given cURL operation should only take
    // 10 seconds max
    const TIMEOUT = 10;

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var string
     */
    private $connectionTimeout;

    /**
     * @var string
     */
    private $timeout;

    /**
     * @param string $baseUri
     */
    public function __construct(
        $baseUri,
        $connectionTimeout = null,
        $timeout = null
    )
    {
        $this->baseUri = $baseUri;
        $this->connectionTimeout = $connectionTimeout === null ? self::CONNECTION_TIMEOUT : $connectionTimeout;
        $this->timeout = $timeout === null ? self::TIMEOUT : $timeout;
    }

    /**
     * @param array $events
     *
     * @return void
     *
     * @throws \Exception
     */
    public function report(array $events)
    {
        $url = $this->getUrl();
        $body = Compressor::gzip(
            NDJson::convert($events)
        );
        $headers = $this->getHttpHeaders(
            $this->getHeaders($body)
        );

        $ch = curl_init($url);

        if (false === $ch) {
            throw new \Exception('Could not initialize the curl handler.');
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $response = curl_exec($ch);

        if(curl_errno($ch)){
            throw new Exception(curl_error($ch));
        }

        $httpStatusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (202 !== $httpStatusCode) {
            throw new ReporterException($response, $httpStatusCode);
        }
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return sprintf(
            '%s%s',
            $this->baseUri,
            self::URI
        );
    }

    /**
     * @param array $headers
     *
     * @return array
     *
     * @return array
     */
    private function getHttpHeaders(array $headers)
    {
        return array_map(
            static function ($key, $value) {
                return sprintf(
                    '%s: %s',
                    $key,
                    $value
                );
            },
            array_keys($headers),
            array_values($headers)
        );
    }

    /**
     * @param string $body
     *
     * @return array
     */
    private function getHeaders($body)
    {
        return array_merge(
            $this->defaultRequestHeaders(),
            [
                'Content-Length' => strlen($body),
            ]
        );
    }

    /**
     * @return array
     */
    private function defaultRequestHeaders()
    {
        return [
            'Content-Type' => NDJson::contentType(),
            'Content-Encoding' => 'gzip',
            'User-Agent' => sprintf('%s/%s', Agent::NAME, Agent::VERSION),
            'Accept' => 'application/json',
        ];
    }
}
