<?php

namespace ChiefGroup\Feiyu;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Feiyu
 *
 * @package ChiefGroup\Feiyu
 *
 */
class Feiyu
{
    private $baseUri = 'https://feiyu.oceanengine.com/';
    private $pullAPI = '/crm/v2/openapi/pull-clues/';

    /**
     * @var array $config
     */
    private $config;
    private $signature;
    private $token;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->signature = $config['signature'] ?? '';
        $this->token = $config['token'] ?? '';
    }

    /**
     * @param int $startTime
     * @param int $endTime
     * @param int $pageSize
     * @param callable $callback
     * @param string $signature
     * @param string $token
     *
     * @return bool
     * @throws GuzzleException
     */
    public function pull($startTime, $endTime, $pageSize, $callback, $signature = '', $token = '')
    {
        $signature = $signature ?: $this->signature;
        $token = $token ?: $this->token;

        if (empty($signature) || empty($token)) {
            throw new InvalidArgumentException('signature or token is required');
        }

        $queryParams = [
            'start_time' => $startTime,
            'end_time'   => $endTime
        ];

        $path = $this->pullAPI . '?';
        $httpClient = $this->_getClient();
        $headers = $this->_reqHeaders($path . http_build_query($queryParams), $signature, $token);

        $page = 1;
        $queryParams['page_size'] = $pageSize;

        do {
            $queryParams['page'] = $page;
            $response = $httpClient->request('GET', $path . http_build_query($queryParams), ['headers' => $headers]);
            $resultArr = json_decode($response->getBody()->getContents(), true);

            if ($resultArr['status'] != 'success' || $resultArr['count'] == 0) {
                break;
            }
            $count = $resultArr['count'];

            if ($callback($resultArr['data']) === false) {
                return false;
            }
            unset($resultArr);

            $page++;
        } while (($page - 1) * $pageSize < $count);

        return true;
    }

    /**
     * @param $path
     * @param $signature
     * @param $token
     * @return array[]
     */
    private function _reqHeaders($path, $signature, $token): array
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
        $logFile = $this->config['log']['file'] ?? __DIR__ . '/../log/feiyu.log';
        $logLevel = $this->config['log']['level'] ?? 'error';

        $logger = new Logger('feiyu');
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));
        $formatter = new MessageFormatter(MessageFormatter::DEBUG);

        $logMiddleware = Middleware::log($logger, $formatter);
        $mapResponse = Middleware::mapResponse(function (ResponseInterface $response) {
            $response->getBody()->rewind();
            return $response;
        });

        $stack = HandlerStack::create();
        $stack->push($mapResponse);
        $stack->push($logMiddleware, 'log');

        return new Client([
            'handler'  => $stack,
            'base_uri' => $this->baseUri,
            'timeout'  => 10,
        ]);
    }

}
