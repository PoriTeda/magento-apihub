<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\PaymentTest
 */
namespace Magento\CustomerBalance\Test\Unit\Block\Adminhtml\Sales\Order\Create;

class PaymentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested class
     *
     * @var string
     */
    protected $_className;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactoryMock;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuoteMock;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $_orderCreateMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_helperMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_balanceInstance;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeMock;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrency;

    /**
     * initialize arguments for construct
     */
    protected function setUp()
    {
        $this->priceCurrency = $this->getMockBuilder(
            \Magento\Framework\Pricing\PriceCurrencyInterface::class
        )->getMock();
        $this->_balanceInstance = $this->createPartialMock(
            \Magento\CustomerBalance\Model\Balance::class,
            ['setCustomerId', 'setWebsiteId', 'getAmount', 'loadByCustomer', '__wakeup']
        );
        $this->_balanceFactoryMock = $this->createPartialMock(
            \Magento\CustomerBalance\Model\BalanceFactory::class,
            ['create']
        );
        $this->_balanceFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->_balanceInstance)
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'setCustomerId'
        )->will(
            $this->returnValue($this->_balanceInstance)
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'setWebsiteId'
        )->will(
            $this->returnValue($this->_balanceInstance)
        );
        $this->_balanceInstance->expects(
            $this->any()
        )->method(
            'loadByCustomer'
        )->will(
            $this->returnValue($this->_balanceInstance)
        );
        $this->_sessionQuoteMock = $this->createMock(\Magento\Backend\Model\Session\Quote::class);
        $this->_orderCreateMock = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $this->_storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getCustomerId', 'getStoreId', '__wakeup']
        );
        $this->_orderCreateMock->expects($this->any())->method('getQuote')->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->any())->method('getCustomerId')->will($this->returnValue(true));
        $quoteMock->expects($this->any())->method('getStoreId')->will($this->returnValue(true));
        $this->_helperMock = $this->createMock(\Magento\CustomerBalance\Helper\Data::class);

        $this->_storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->_storeManagerMock->expects(
            $this->any()
        )->method(
            'getStore'
        )->will(
            $this->returnValue($this->_storeMock)
        );

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_className = $helper->getObject(
            \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::class,
            [
                'storeManager' => $this->_storeManagerMock,
                'sessionQuote' => $this->_sessionQuoteMock,
                'orderCreate' => $this->_orderCreateMock,
                'priceCurrency' => $this->priceCurrency,
                'balanceFactory' => $this->_balanceFactoryMock,
                'customerBalanceHelper' => $this->_helperMock
            ]
        );
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * Check case when customer balance is disabled
     */
    public function testGetBalanceNotEnabled()
    {
        $this->_helperMock->expects($this->once())->method('isEnabled')->will($this->returnValue(false));

        $result = $this->_className->getBalance();
        $this->assertEquals(0.0, $result);
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * Test if need to use converting price by current currency rate
     */
    public function testGetBalanceConvertPrice()
    {
        $this->_helperMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $amount = rand(1, 100);
        $convertedAmount = $amount * 2;

        $this->_balanceInstance->expects($this->once())->method('getAmount')->will($this->returnValue($amount));
        $this->priceCurrency->expects($this->once())
            ->method('convert')
            ->with($this->equalTo($amount))
            ->willReturn($convertedAmount);
        $result = $this->_className->getBalance(true);
        $this->assertEquals($convertedAmount, $result);
    }

    /**
     * Test \Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment::getBalance()
     * No additional cases, standard behaviour
     */
    public function testGetBalanceAmount()
    {
        $amount = rand(1, 1000);
        $this->_helperMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->_balanceInstance->expects($this->once())->method('getAmount')->will($this->returnValue($amount));
        $result = $this->_className->getBalance();
        $this->assertEquals($amount, $result);
    }
}
