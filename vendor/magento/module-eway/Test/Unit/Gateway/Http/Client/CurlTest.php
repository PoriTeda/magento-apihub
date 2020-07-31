<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Http\Client;

use Zend_Http_Response;
use Magento\Framework\HTTP\Adapter;
use Magento\Payment\Model\Method\Logger;
use Magento\Eway\Gateway\Http\Client\Curl;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Eway\Gateway\Http\Client\ResponseFactory;

/**
 * Class Test
 *
 * Test for class \Magento\Eway\Gateway\Http\Client\Curl
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurlTest extends \PHPUnit\Framework\TestCase
{
    const BODY_DATA = 'test-data';

    const URL_DATA = 'test-url';

    const AUTH_USERNAME_DATA = 'test-username';

    const AUTH_PASSWORD_DATA = 'test-password';

    const METHOD_DATA = 'test-method';

    const RESPONSE_DATA = 'test-response';

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converterMock;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ResponseFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responseFactoryMock;

    /**
     * @var Adapter\Curl|\PHPUnit_Framework_MockObject_MockObject
     */
    private $curlMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->converterMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\ConverterInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(\Magento\Payment\Model\Method\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseFactoryMock = $this->getMockBuilder(\Magento\Eway\Gateway\Http\Client\ResponseFactory::class)
            ->getMock();
        $this->curlMock = $this->getMockBuilder(\Magento\Framework\HTTP\Adapter\Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->curl = new Curl($this->loggerMock, $this->converterMock, $this->responseFactoryMock, $this->curlMock);
    }

    /**
     * Run test placeRequest method (exception)
     *
     * @return void
     *
     * @expectedException \Magento\Payment\Gateway\Http\ClientException
     * @expectedExceptionMessage Test messages
     */
    public function testPlaceRequestException()
    {
        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => json_encode(self::BODY_DATA, JSON_UNESCAPED_SLASHES),
                    'request_uri' => self::URL_DATA,
                    'response' => []
                ]
            );

        $this->curlMock->expects($this->once())
            ->method('setOptions')
            ->willThrowException(new \Exception('Test messages'));
        $this->curlMock->expects($this->never())
            ->method('write');

        $this->converterMock->expects($this->never())
            ->method('convert');

        /** @var TransferInterface|\PHPUnit_Framework_MockObject_MockObject $transferObjectMock */
        $transferObjectMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->getMockForAbstractClass();

        $transferObjectMock->expects($this->once())
            ->method('getBody')
            ->willReturn(self::BODY_DATA);
        $transferObjectMock->expects($this->once())
            ->method('getUri')
            ->willReturn(self::URL_DATA);

        $this->curl->placeRequest($transferObjectMock);
    }

    /**
     * Run test placeRequest method
     *
     * @return void
     */
    public function testPlaceRequestSuccess()
    {
        $convertedData = $this->getConvertedData();
        $headers = [];
        foreach ($this->getHeadersData() as $name => $value) {
            $headers[] = sprintf('%s: %s', $name, $value);
        }

        $this->curlMock->expects($this->once())
            ->method('setOptions')
            ->with($this->getOptionsData());
        $this->curlMock->expects($this->once())
            ->method('write')
            ->with(
                self::METHOD_DATA,
                self::URL_DATA,
                Curl::HTTP_1,
                $headers,
                self::BODY_DATA
            );

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with(
                [
                    'request' => json_encode(self::BODY_DATA, JSON_UNESCAPED_SLASHES),
                    'request_uri' => self::URL_DATA,
                    'response' => $convertedData
                ]
            );
        $this->responseFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->getResponseMock());
        $this->converterMock->expects($this->once())
            ->method('convert')
            ->with(self::RESPONSE_DATA)
            ->willReturn($convertedData);

        $actualResult = $this->curl->placeRequest($this->getTransferObjectMock());

        $this->assertTrue(is_array($actualResult));
        $this->assertEquals($convertedData, $actualResult);
    }

    /**
     * @return Zend_Http_Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getResponseMock()
    {
        $responseMock = $this->getMockBuilder(\Zend_Http_Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn(self::RESPONSE_DATA);

        return $responseMock;
    }

    /**
     * @return TransferInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTransferObjectMock()
    {
        $transferObjectMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->getMockForAbstractClass();

        $transferObjectMock->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn(self::BODY_DATA);
        $transferObjectMock->expects($this->exactly(2))
            ->method('getUri')
            ->willReturn(self::URL_DATA);
        $transferObjectMock->expects($this->once())
            ->method('getAuthUsername')
            ->willReturn(self::AUTH_USERNAME_DATA);
        $transferObjectMock->expects($this->once())
            ->method('getAuthPassword')
            ->willReturn(self::AUTH_PASSWORD_DATA);
        $transferObjectMock->expects($this->once())
            ->method('getHeaders')
            ->willReturn($this->getHeadersData());
        $transferObjectMock->expects($this->once())
            ->method('getMethod')
            ->willReturn(self::METHOD_DATA);

        return $transferObjectMock;
    }

    /**
     * @return array
     */
    private function getHeadersData()
    {
        return [
            'test-key' => 'test-value'
        ];
    }

    /**
     * @return array
     */
    private function getConvertedData()
    {
        return [
            'test-converted-key' => 'test-converted-data'
        ];
    }

    /**
     * @return array
     */
    private function getOptionsData()
    {
        return [
            CURLOPT_USERPWD => self::AUTH_USERNAME_DATA . ":" . self::AUTH_PASSWORD_DATA,
            CURLOPT_TIMEOUT => Curl::REQUEST_TIMEOUT
        ];
    }
}
