<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerBalance\Test\Unit\Observer;

use Magento\CustomerBalance\Observer\CreditmemoDataImportObserver;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Math\FloatComparator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class CreditmemoDataImportObserverTest
 */
class CreditmemoDataImportObserverTest extends \PHPUnit\Framework\TestCase
{
    private static $refundAmount = 10;

    /**
     * @var CreditmemoDataImportObserver
     */
    private $model;

    /**
     * @var PriceCurrency|MockObject
     */
    private $priceCurrency;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var DataObject|MockObject
     */
    private $event;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemo;

    protected function setUp()
    {
        $this->priceCurrency = $this->getMockBuilder(PriceCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->observer = $objectManagerHelper->getObject(
            Observer::class,
            ['event' => $this->event]
        );

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOrder'])
            ->getMock();

        $this->model = $objectManagerHelper->getObject(
            CreditmemoDataImportObserver::class,
            [
                'priceCurrency' => $this->priceCurrency,
                'floatComparator' => new FloatComparator()
            ]
        );
    }

    public function testCreditmemoDataImport()
    {
        $rate = 2;
        $dataInput = [
            'refund_customerbalance_return' => self::$refundAmount,
            'refund_customerbalance_return_enable' => true,
            'refund_customerbalance' => true,
            'refund_real_customerbalance' => true,
        ];

        $observer = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->creditmemo->setBaseCustomerBalanceReturnMax(self::$refundAmount);
        $this->creditmemo->setBaseGrandTotal(self::$refundAmount);

        $this->priceCurrency->expects($this->at(0))
            ->method('round')
            ->with(self::$refundAmount)
            ->willReturnArgument(0);
        $this->priceCurrency->expects($this->at(1))
            ->method('round')
            ->with(self::$refundAmount * $rate)
            ->willReturnArgument(0);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseToOrderRate'])
            ->getMock();
        $orderMock->expects($this->once())
            ->method('getBaseToOrderRate')
            ->willReturn($rate);

        $this->creditmemo->method('getOrder')
            ->willReturn($orderMock);

        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCreditmemo', 'getInput'])
            ->getMock();
        $eventMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemo);
        $eventMock->expects($this->once())
            ->method('getInput')
            ->willReturn($dataInput);
        $observer->method('getEvent')
            ->willReturn($eventMock);

        $this->model->execute($observer);
        $this->assertEquals($this->creditmemo->getCustomerBalanceRefundFlag(), true);
        $this->assertEquals($this->creditmemo->getPaymentRefundDisallowed(), true);
        $this->assertEquals($this->creditmemo->getCustomerBalTotalRefunded(), self::$refundAmount * $rate);
        $this->assertEquals(0, $this->creditmemo->getBaseGrandTotal());
        $this->assertEquals(self::$refundAmount, $this->creditmemo->getBaseCustomerBalanceReturnMax());
    }
}
