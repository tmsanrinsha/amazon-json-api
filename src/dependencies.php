<?php
// DIC configuration

use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;
use ApaiIO\ApaiIO;

$container = $app->getContainer();

$container['lookup'] = function ($c) {
    return new Lookup();
};

$container['apaiio'] = function ($c) {
    $client = new \GuzzleHttp\Client();
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
