<?php

namespace ChiefGroup\Kernel\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;

trait HasHttpRequests
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var \GuzzleHttp\HandlerStack
     */
    protected $handlerStack;

    /**
     * @var array
     */
    protected static $defaults = [
        'curl' => [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ],
    ];

    /**
     * Set guzzle default settings.
     *
     * @param array $defaults
     */
    public static function setDefaultOptions($defaults = [])
    {
        self::$defaults = $defaults;
    }

    /**
     * Return current guzzle default settings.
     */
    public static function getDefaultOptions(): array
    {
        return self::$defaults;
    }

    /**
     * Set GuzzleHttp\Client.
     *
     * @return \ChiefGroup\Kernel\Traits\HasHttpRequests
     */
    public function setHttpClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * Return GuzzleHttp\ClientInterface instance.
     */
    public function getHttpClient(): ClientInterface
    {
        if (!($this->httpClient instanceof ClientInterface)) {
            $this->httpClient = new Client(['handler' => HandlerStack::create($this->getGuzzleHandler())]);
        }

        return $this->httpClient;
    }

    /**
     * Add a middleware.
     *
     * @param string $name
     *
     * @return $this
     */
    public function pushMiddleware(callable $middleware, string $name = null)
    {
        if (!is_null($name)) {
            $this->middlewares[$name] = $middleware;
        } else {
            array_push($this->middlewares, $middleware);
        }

        return $this;
    }

    /**
     * Return all middlewares.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Make a request.
     *
     * @param string $url
     * @param string $method
     * @param array  $options
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($url, $method = 'GET', $options = []): ResponseInterface
    {
        $method = strtoupper($method);

//        $options = array_merge(self::$defaults, $options, ['handler' => $this->getHandlerStack()]);
        $options = array_merge(self::$defaults, $options);

        $options = $this->fixJsonIssue($options);

        if (property_exists($this, 'baseUri') && !is_null($this->baseUri)) {
            $options['base_uri'] = $this->baseUri;
        }

        $response = $this->getHttpClient()->request($method, $url, $options);
        $response->getBody()->rewind();

        return $response;
    }

    protected function fixJsonIssue(array $options): array
    {
        if (isset($options['json']) && is_array($options['json'])) {
            $options['headers'] = array_merge($options['headers'] ?? [], ['Content-Type' => 'application/json']);

            if (empty($options['json'])) {
                $options['body'] = \GuzzleHttp\json_encode($options['json'], JSON_FORCE_OBJECT);
            } else {
                $options['body'] = \GuzzleHttp\json_encode($options['json'], JSON_UNESCAPED_UNICODE);
            }

            unset($options['json']);
        }

        return $options;
    }

    /**
     * Build a handler stack.
     */
    public function getHandlerStack(): HandlerStack
    {
        if ($this->handlerStack) {
            return $this->handlerStack;
        }

        $this->handlerStack = HandlerStack::create($this->getGuzzleHandler());

        foreach ($this->middlewares as $name => $middleware) {
            $this->handlerStack->push($middleware, $name);
        }

        return $this->handlerStack;
    }

    /**
     * Get guzzle handler.
     *
     * @return callable
     */
    protected function getGuzzleHandler()
    {

        return \GuzzleHttp\choose_handler();
    }
}