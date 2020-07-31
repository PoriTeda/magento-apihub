<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Observer;

class CheckoutProcessWrappingInfoTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftWrapping\Observer\CheckoutProcessWrappingInfo */
    protected $_model;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemInfoManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventMock;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->itemInfoManager = $this->createMock(\Magento\GiftWrapping\Observer\ItemInfoManager::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventMock = $this->createPartialMock(\Magento\Framework\Event::class, [
                'getQuote',
                'getItems',
                'getOrder',
                'getOrderItem',
                'getQuoteItem',
                '__wakeup'
            ]);
        $this->_model = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Observer\CheckoutProcessWrappingInfo::class,
            [
                'itemInfoManager' =>  $this->itemInfoManager
            ]
        );
        $this->_event = new \Magento\Framework\DataObject();
    }

    public function testCheckoutProcessWrappingInfoQuote()
    {
        $giftWrappingInfo = ['quote' => [1 => ['some data']]];
        $requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $event = new \Magento\Framework\Event(['request' => $requestMock, 'quote' => $quoteMock]);
        $observer = new \Magento\Framework\Event\Observer(['event' => $event]);

        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('giftwrapping')
            ->will($this->returnValue($giftWrappingInfo));

        $this->itemInfoManager->expects($this->once())->method('saveOrderInfo')->with($quoteMock, ['some data'])
            ->willReturnSelf();
//        $quoteMock->expects($this->once())->method('getShippingAddress')->will($this->returnValue(false));
//        $quoteMock->expects($this->once())->method('addData')->will($this->returnSelf());
        $quoteMock->expects($this->never())->method('getAddressById');
        $this->_model->execute($observer);
    }
}
