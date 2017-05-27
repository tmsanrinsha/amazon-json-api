<?php
// Routes

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

$app->get('/asin/{asin}', function ($request, $response, $args) {
    $asin = $request->getAttribute('asin');
    $this->lookup->setItemId($asin);
    $this->lookup->setResponseGroup(['Images', 'Small']);

    try {
        $res = $this->apaiio->runOperation($this->lookup);
    } catch(RequestException $e) {
        $this->logger->error(Psr7\str($e->getRequest()));
        $this->logger->error(Psr7\str($e->getResponse()));
        return $response->withJson([
            "error" => [
                "request" => Psr7\str($e->getRequest()),
                "response" => Psr7\str($e->getResponse()),
            ]
        ], $e->getCode());
    }

    $results = simplexml_load_string($res);

    if (!(bool)$results->Items->Request->IsValid) {
        $message = "Request is invalid. (ASIN: $asin)";
        $this->logger->error($message);
        return $response->withJson([
            "error" => [
                "message" => $message
            ]
        ], 400);
    } elseif (empty($results->Items->Item[0])) {
        $message = "Item is not found. (ASIN: $asin)";
        $this->logger->error($message);
        return $response->withJson([
            "error" => [
                "message" => $message
            ]
        ], 404);
    }

    $item = $results->Items->Item[0];


    return $response->withJson([
        "asin"=> (string) $item->ASIN,
        "title"=> (string) $item->ItemAttributes->Title,
        "author" => (string) $item->ItemAttributes->Author,
        "manufacturer" => (string) $item->ItemAttributes->Manufacturer,
        "item_url"=> (string) $item_url = $item->DetailPageURL,
        "image_url"=> (string) $item->MediumImage->URL,
    ]);
});
