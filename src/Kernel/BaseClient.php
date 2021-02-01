<?php
namespace ChiefGroup\Kernel;


use ChiefGroup\Kernel\Traits\HasHttpRequests;

class BaseClient
{
    use HasHttpRequests { request as performRequest; }

    /**
     * GET request.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpGet(string $url, array $query = [])
    {
        return $this->request($url, 'GET', ['query' => $query]);
    }

    /**
     * POST request.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function httpPost(string $url, array $data = [])
    {
        return $this->request($url, 'POST', ['form_params' => $data]);
    }

    /**
     * @param bool $returnRaw
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $url, string $method = 'GET', array $options = [])
    {
        if (empty($this->middlewares)) {
            $this->registerHttpMiddlewares();
        }

        return $this->performRequest($url, $method, $options);

    }

    /**
     * Register Guzzle middlewares.
     */
    protected function registerHttpMiddlewares()
    {

    }

}