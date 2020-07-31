<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Controller\HtmlRedirect;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Magento\Worldpay\Controller\HtmlRedirect\GetTransactionData;
use Magento\Worldpay\Model\Api\PlaceTransactionService;

/**
 * Class GetTransactionDataTest
 * @package Magento\Worldpay\Test\Unit\Controller\HtmlRedirect
 * @see Magento\Worldpay\Controller\HtmlRedirect\GetTransactionData
 */
class GetTransactionDataTest extends \PHPUnit\Framework\TestCase
{
    const ORDER_ID = 23;
    const ORDER_SESSION_KEY = 'last_order_id';

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;

    /**
     * @var Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonMock;

    /**
     * @var PlaceTransactionService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $placeTransactionServiceMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeTransactionServiceMock = $this->getMockBuilder(
            \Magento\Worldpay\Model\Api\PlaceTransactionService::class
        )
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @covers \Magento\Worldpay\Controller\HtmlRedirect\GetTransactionData::execute
     * @return void
     */
    public function testExecuteException()
    {
        $this->sessionMock->expects(static::once())
            ->method('getData')
            ->with(self::ORDER_SESSION_KEY)
            ->willReturn(false);

        $this->contextMock->expects(static::once())
            ->method('getResultFactory')
            ->willReturn($this->getResultFactoryMock());

        $this->jsonMock->expects(static::once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST)
            ->willReturnSelf();
        $this->jsonMock->expects(static::once())
            ->method('setData')
            ->with(['message' => 'No such order id.'])
            ->willReturnSelf();

        $getTransactionData = new GetTransactionData(
            $this->contextMock,
            $this->placeTransactionServiceMock,
            $this->sessionMock
        );
        $getTransactionData->execute();
    }

    /**
     * @covers \Magento\Worldpay\Controller\HtmlRedirect\GetTransactionData::execute
     * @param array $response
     * @return void
     *
     * @dataProvider dataProviderTestExecute
     */
    public function testExecute(array $response)
    {
        $this->sessionMock->expects(static::once())
            ->method('getData')
            ->with(self::ORDER_SESSION_KEY)
            ->willReturn(self::ORDER_ID);

        $this->contextMock->expects(static::once())
            ->method('getResultFactory')
            ->willReturn($this->getResultFactoryMock());

        $this->placeTransactionServiceMock->expects(static::once())
            ->method('placeTransaction')
            ->with(self::ORDER_ID)
            ->willReturn($response);

        $this->jsonMock->expects(static::once())
            ->method('setData')
            ->with($response)
            ->willReturnSelf();

        $getTransactionData = new GetTransactionData(
            $this->contextMock,
            $this->placeTransactionServiceMock,
            $this->sessionMock
        );

        $getTransactionData->execute();
    }

    /**
     * Create data for tests
     * @return array
     */
    public function dataProviderTestExecute()
    {
        return [
            [
                'response' => [
                    'action' => 'worldpay sandbox url must be here',
                    'fields' => [
                        'MC_order_id', 'MC_store_id', 'authAmountString', 'callbackPW', 'msgType', 'region'

                    ],
                    'values' => [
                        self::ORDER_ID, 1, 'US&#36;15.00', '****', 'authResult', 'Colorado'
                    ]
                ]
            ]
        ];
    }

    /**
     * Get mock for result factory
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getResultFactoryMock()
    {
        $resultFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultFactoryMock->expects(static::once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($this->jsonMock);

        return $resultFactoryMock;
    }
}
