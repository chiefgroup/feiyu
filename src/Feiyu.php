<?php

namespace ChiefGroup\Feiyu;

use ChiefGroup\Kernel\BaseClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Feiyu
 *
 * @package ChiefGroup\Feiyu
 *
 */
class Feiyu
{
    private $client;

    /**
     * @var BaseClient $baseClient
     */
    private $baseClient;

    public function __construct()
    {

    }

    /**
     * @param $signature
     * @param $token
     * @param $startTime
     * @param $endTime
     * @param int $page
     * @param int $pageSize
     *
     * @return array|mixed
     * @throws GuzzleException
     */
    public function getClues($signature, $token, $startTime, $endTime, $page = 1, $pageSize = 10)
    {

        $path = "/crm/v2/openapi/pull-clues/?start_time={$startTime}&end_time={$endTime}";
        $headers = $this->_headers($path, $signature, $token);

        $path .= "&page={$page}&page_size={$pageSize}";
        $response = $this->_getClient()->request('GET', $path, ['headers' => $headers]);


        return $response->getBody()->getContents();
    }

    /**
     * @param $path
     * @param $signature
     * @param $token
     * @return array[]
     */
    private function _headers($path,$signature, $token): array
    {
        $time = time();
        return [
                'Accept'       => 'application/json',
                'Signature'    => base64_encode(hash_hmac('sha256', $path . ' ' . $time, $signature)),
                'Timestamp'    => $time,
                'Access-Token' => $token,
        ];
    }

    /**
     *
     */
    private function _getClient()
    {
        if (!$this->client) {
            $stack = HandlerStack::create();

            $stack->push(Middleware::tap(function (RequestInterface $request, $options) {


            }, function (RequestInterface $request, $options, PromiseInterface $response) {

                $response->then(function (ResponseInterface $response) {
                    if ($response->getStatusCode() > 200) {

                    }
                });
            }));

            $this->client = new Client([
                'handler'  => $stack,
                'timeout' => 15,
                'base_uri' => 'https://feiyu.oceanengine.com/'
            ]);
        }

        return $this->client;
    }

}
