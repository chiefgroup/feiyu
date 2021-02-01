<?php

namespace ChiefGroup;

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
 * @package ChiefGroup
 *
 */
class Feiyu
{
    /**
     * @var BaseClient $baseClient
     */
    private $baseClient;

    public function __construct()
    {
        $this->baseClient = new BaseClient();
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
    public function pullClues($signature, $token, $startTime, $endTime, $page = 1, $pageSize = 10)
    {

        $uri = "/crm/v2/openapi/pull-clues/?start_time={$startTime}&end_time={$endTime}&page={$page}&page_size={$pageSize}";
        $headers = $this->_headers($startTime, $endTime, $signature, $token);

        $response = $this->baseClient->request('GET', $uri, ['headers' => $headers, 'base_uri' => 'https://feiyu.oceanengine.com', 'json']);

        var_dump($response);
        exit();
    }

    /**
     * @param $startTime
     * @param $endTime
     * @param $signature
     * @param $token
     * @return array[]
     */
    private function _headers($startTime, $endTime, $signature, $token): array
    {
        return [
                'Accept'       => 'application/json',
                'Signature'    => $this->_signature($startTime, $endTime, $signature),
                'Timestamp'    => time(),
                'Access-Token' => $token,
        ];
    }

    /**
     * @param $startTime
     * @param $endTime
     * @param $signatureKey
     *
     * @return string
     */
    private function _signature($startTime, $endTime, $signatureKey): string
    {
        $path = "/crm/v2/openapi/pull-clues/?start_time={$startTime}&end_time={$endTime} " . time();
        return base64_encode(hash_hmac('sha256', $path, $signatureKey));
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
