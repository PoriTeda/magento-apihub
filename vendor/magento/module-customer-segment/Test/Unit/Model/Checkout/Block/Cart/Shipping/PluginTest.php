<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Checkout\Block\Cart\Shipping;

use Magento\Quote\Api\Data\EstimateAddressInterfaceFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Cart\CollectQuote;
use Magento\CustomerSegment\Model\Checkout\Block\Cart\Shipping\Plugin as ShippingPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingPlugin
     */
    private $plugin;

    /**
     * @var EstimateAddressInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $addressFactory;

    /**
     * @var CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteRepository;

    /**
     * @var CustomerSession|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepository;

    /**
     * @var Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     * @var CollectQuote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectQuote;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->addressFactory = $this->getMockBuilder(EstimateAddressInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn'])
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectQuote = $this->getMockBuilder(CollectQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = $objectManager->getObject(
            ShippingPlugin::class,
            [
                'addressFactory' => $this->addressFactory,
                'quoteRepository' => $this->quoteRepository,
                'customerSession' => $this->customerSession,
                'customerRepository' => $this->customerRepository
            ]
        );
    }

    public function testBeforeCollect()
    {
        $this->customerSession->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->assertSame(
            [$this->quote],
            $this->plugin->beforeCollect($this->collectQuote, $this->quote)
        );
    }
}
