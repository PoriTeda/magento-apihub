<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Gateway\Http\Client;

use Magento\Framework\HTTP\Adapter;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ConverterInterface;

/**
 * Class Curl
 */
class Curl implements ClientInterface
{
    /**
     * HTTP protocol versions
     */
    const HTTP_1 = '1.1';
    const HTTP_0 = '1.0';

    /**
     * HTTP request methods
     */
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const HEAD    = 'HEAD';
    const DELETE  = 'DELETE';
    const TRACE   = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE   = 'MERGE';
    const PATCH   = 'PATCH';

    /**
     * Request timeout
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Adapter\Curl
     */
    private $curl;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param ConverterInterface $converter
     * @param ResponseFactory $responseFactory
     * @param Adapter\Curl $curl
     */
    public function __construct(
        Logger $logger,
        ConverterInterface $converter,
        ResponseFactory $responseFactory,
        Adapter\Curl $curl
    ) {
        $this->logger = $logger;
        $this->converter = $converter;
        $this->responseFactory = $responseFactory;
        $this->curl = $curl;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => json_encode($transferObject->getBody(), JSON_UNESCAPED_SLASHES),
            'request_uri' => $transferObject->getUri()
        ];
        $response = [];

        try {
            $this->curl->setOptions(
                [
                    CURLOPT_USERPWD => $transferObject->getAuthUsername() . ":" . $transferObject->getAuthPassword(),
                    CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT
                ]
            );
            $headers = [];
            foreach ($transferObject->getHeaders() as $name => $value) {
                $headers[] = sprintf('%s: %s', $name, $value);
            }
            $this->curl->write(
                $transferObject->getMethod(),
                $transferObject->getUri(),
                self::HTTP_1,
                $headers,
                $transferObject->getBody()
            );

            $response = $this->converter->convert($this->read());
        } catch (\Exception $e) {
            throw new ClientException(__($e->getMessage()));
        } finally {
            $log['response'] = $response;
            $this->logger->debug($log);
        }

        return (array) $response;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        return $this->responseFactory->create($this->curl->read())->getBody();
    }
}
