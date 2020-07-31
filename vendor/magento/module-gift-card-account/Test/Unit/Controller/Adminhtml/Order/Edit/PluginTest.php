<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Controller\Adminhtml\Order\Edit;

use Magento\GiftCardAccount\Model\Giftcardaccount;

/**
 * Class PluginTest
 */
class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\CustomerBalance\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerBalanceData;

    /**
     * @var \Magento\Backend\Model\Session\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionQuote;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCardAccountData;

    protected function setUp()
    {
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->customerBalanceData = $this->getMockBuilder(\Magento\CustomerBalance\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote = $this->getMockBuilder(\Magento\Backend\Model\Session\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->giftCardAccountData = $this->getMockBuilder(\Magento\GiftCardAccount\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->plugin = $objectManagerHelper->getObject(
            \Magento\GiftCardAccount\Controller\Adminhtml\Order\Edit\Plugin::class,
            [
                'sessionQuote' => $this->sessionQuote,
                'messageManager' => $this->messageManager,
                'customerBalanceData' => $this->customerBalanceData,
                'giftCardAccountData' => $this->giftCardAccountData
            ]
        );
    }

    protected function initMocksGiftCardAccountData($giftCards)
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuote->expects($this->atLeastOnce())
            ->method('getOrder')
            ->will($this->returnValue($orderMock));

        $this->giftCardAccountData->expects($this->atLeastOnce())
            ->method('getCards')
            ->with($orderMock)
            ->will($this->returnValue($giftCards));
    }

    public function testBeforeIndexActionWithoutGiftCards()
    {
        $this->initMocksGiftCardAccountData([]);

        $this->customerBalanceData->expects($this->never())->method('isEnabled');
        $this->messageManager->expects($this->never())->method('addNotice');
        $this->messageManager->expects($this->never())->method('addError');

        $controllerOrderEdit = $this->getMockBuilder(\Magento\Sales\Controller\Adminhtml\Order\Edit\Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }

    public function testBeforeIndexActionStoreCreditEnable()
    {
        $giftCards = [
            Giftcardaccount::BASE_AMOUNT => 50,
            Giftcardaccount::AMOUNT => 50,
            Giftcardaccount::CODE => 'someCode'
        ];
        $this->initMocksGiftCardAccountData($giftCards);

        $this->customerBalanceData->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->messageManager->expects($this->once())
            ->method('addNotice')
            ->with('We will refund the gift card amount to your customer’s store credit');
        $this->messageManager->expects($this->never())->method('addError');

        $controllerOrderEdit = $this->getMockBuilder(\Magento\Sales\Controller\Adminhtml\Order\Edit\Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }

    public function testBeforeIndexActionStoreCreditDisabled()
    {
        $giftCards = [
            Giftcardaccount::BASE_AMOUNT => 50,
            Giftcardaccount::AMOUNT => 50,
            Giftcardaccount::CODE => 'someCode'
        ];
        $this->initMocksGiftCardAccountData($giftCards);

        $this->customerBalanceData->expects($this->once())->method('isEnabled')->will($this->returnValue(false));
        $this->messageManager->expects($this->once())
            ->method('addNotice')
            ->with('We will refund the gift card amount to your customer’s store credit');
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Please enable Store Credit to refund the gift card amount to your customer');

        $controllerOrderEdit = $this->getMockBuilder(\Magento\Sales\Controller\Adminhtml\Order\Edit\Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin->beforeExecute($controllerOrderEdit);
    }
}
