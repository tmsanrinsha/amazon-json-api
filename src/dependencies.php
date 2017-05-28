<?php
// DIC configuration

use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use ApaiIO\ApaiIO;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

$container = $app->getContainer();

$container['lookup'] = function ($c) {
    return new Lookup();
};

$container['apaiio'] = function ($c) {
    //リトライ判断
    $decider = function (
        $retries,
        Request $request,
        Response $response = null,
        RequestException $e = null
    ) use ($c) {
        if ($retries >= 5) {
            return false;
        }

        $shouldRetry = false;

        if ($e instanceof ConnectException) {
            $shouldRetry = true;
        }

        if ($response) {
           if ($response->getStatusCode() >= 500) {
               $shouldRetry = true;
           }
        }

        if ($shouldRetry) {
            $c->logger->warning(
                sprintf(
                    'Retrying %s %s %s/5, %s',
                    $request->getMethod(),
                    $request->getUri(),
                    $retries + 1,
                    $response ? 'status code: ' . $response->getStatusCode() :
                    $e->getMessage()
                )
            );
        }

        return $shouldRetry;
    };

    //遅延時間を返す
    $delay = function ($retries) {
        return 1000; // 1000ミリ秒待つ
    };

    $handlerStack = HandlerStack::create(new CurlHandler());
    $handlerStack->push(Middleware::retry($decider, $delay));
    $client = new Client(['handler' => $handlerStack, 'timeout' => 5]);

    $request = new \ApaiIO\Request\GuzzleRequest($client);

    $conf = new GenericConfiguration();
    $conf
        ->setCountry('co.jp')
        ->setAccessKey($c['settings']['amazon']['AWSAccessKeyId'])
        ->setSecretKey($c['settings']['amazon']['AWSSecretKey'])
        ->setAssociateTag($c['settings']['amazon']['AWSAssociateTag'])
        ->setRequest($request);

    return new ApaiIO($conf);
};


// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};
