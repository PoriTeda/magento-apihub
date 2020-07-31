<?php

/**
 * Examples of REST API usage
 */
require __DIR__ . '/app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication('Magento\Framework\App\Http');

$token = 'e4s3rvq2py6h6jm2pi39c73im36tj6t9';
$httpHeaders = new \Zend\Http\Headers();
$httpHeaders->addHeaders(
    [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]
);
$client = new \Zend\Http\Client();
$options = [
    'adapter' => 'Zend\Http\Client\Adapter\Curl',
    'curloptions' => [CURLOPT_FOLLOWLOCATION => true],
    'maxredirects' => 0,
    'timeout' => 30
];
$client->setOptions($options);



$request = new \Zend\Http\Request();
$request->setHeaders($httpHeaders);

/**
 * Get stock data for one product
 */
$request->setMethod(\Zend\Http\Request::METHOD_GET);
$request->setUri('http://magento2.wyomind.com/rest/V1/stock/1');
$response = $client->send($request);
echo $response->getContent();

/**
 * Get all warehouses/points of sale
 */
$request->setMethod(\Zend\Http\Request::METHOD_GET);
$request->setUri('http://magento2.wyomind.com/rest/V1/pos/');
$response = $client->send($request);
echo $response->getContent();

/**
 * Get all warehouses/points of sale for one store view
 */
$request->setMethod(\Zend\Http\Request::METHOD_GET);
$request->setUri('http://magento2.wyomind.com/rest/V1/posbystoreid/1');
$response = $client->send($request);
echo $response->getContent();

/**
 * Get stock data for a group of warehouses
 */
$request->setMethod(\Zend\Http\Request::METHOD_POST);
$request->setUri('http://magento2.wyomind.com/rest/V1/stockbyproductidandplaceids');
$params = new \Zend\Stdlib\Parameters(['productId' => 1, 'placeIds' => [541, 542, 543]]);
$request->setQuery($params);
$response = $client->send($request);
echo $response->getContent();

/**
 * Get stock data for a group of store views
 */
$request->setMethod(\Zend\Http\Request::METHOD_POST);
$request->setUri('http://magento2.wyomind.com/rest/V1/stockbyproductidandstoreids');
$params = new \Zend\Stdlib\Parameters(["productId" => 1, 'storeIds' => [1, 2, 3]]);
$request->setQuery($params);
$response = $client->send($request);
echo $response->getContent();

/**
 * Update the stocks settings of one product for one warehouse
 */
$request->setMethod(\Zend\Http\Request::METHOD_POST);
$request->setUri('http://magento2.wyomind.com/rest/V1/updatestock');
$params = new \Zend\Stdlib\Parameters(
    [
    "productId" => 1,
    "multistock_enabled" => 1,
    "placeId" => 541,
    "manageStock" => rand(0, 1),
    "quantityInStock" => rand(0, 100),
    "backorderAllowed" => rand(0, 2),
    "useConfigSettingForBackorders" => rand(0, 1)
        ]
);
$request->setQuery($params);
$response = $client->send($request);
echo $response->getContent();

/**
 * Update the inventory settings for one warehouse
 */
$request->setMethod(\Zend\Http\Request::METHOD_POST);
$request->setUri('http://magento2.wyomind.com/rest/V1/updateinventory');
$params = new \Zend\Stdlib\Parameters(
    [
    "productId" => 1
        ]
);
$request->setQuery($params);
$response = $client->send($request);
echo $response->getContent();
