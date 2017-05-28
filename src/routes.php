<?php
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

$app->get('/asin/{asin}', function ($request, $response, $args) {
    $asin = $request->getAttribute('asin');
    $this->lookup->setItemId($asin);
    $this->lookup->setResponseGroup(['Images', 'Small']);

    try {
        $res = $this->apaiio->runOperation($this->lookup);
    } catch(RequestException $e) {
        $strRequest = Psr7\str($e->getRequest());
        $strResponse = '';
        if (!is_null($e->getResponse())) {
            $strResponse = Psr7\str($e->getResponse());
        }

        $this->logger->error($strRequest);
        $this->logger->error($strResponse);

        return $response->withJson([
            "error" => [
                "request" => $strRequest,
                "response" => $strResponse,
            ]
        ], $e->getCode() ?: 500);
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

    $ret = [
        "PageURL"=> (string) $item->DetailPageURL,
        "ImageURL"=> (string) $item->MediumImage->URL,
        "ImageHeight"=> (string) $item->MediumImage->Height,
        "ImageWidth"=> (string) $item->MediumImage->Width,
    ];

    $ret['ItemAttributes'] = json_decode(json_encode($item->ItemAttributes), true);

    return $response->withJson($ret);
});
