<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Block\Tooltip;

class CheckoutTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareLayout()
    {
        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)->disableOriginalConstructor()->getMock();
        $rewardAction = $this->getMockBuilder(
            \Magento\Reward\Model\Action\AbstractAction::class
        )->disableOriginalConstructor()->getMock();
        $rewardHelper = $this->getMockBuilder(
            \Magento\Reward\Helper\Data::class
        )->disableOriginalConstructor()->setMethods(
            ['isEnabledOnFront']
        )->getMock();
        $customerSession = $this->getMockBuilder(
            \Magento\Customer\Model\Session::class
        )->disableOriginalConstructor()->getMock();
        $rewardInstance = $this->getMockBuilder(
            \Magento\Reward\Model\Reward::class
        )->disableOriginalConstructor()->setMethods(
            ['setWebsiteId', 'setCustomer', 'getActionInstance', '__wakeup']
        )->getMock();
        $storeManager = $this->getMockBuilder(
            \Magento\Store\Model\StoreManager::class
        )->disableOriginalConstructor()->setMethods(
            ['getStore', 'getWebsiteId']
        )->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        /** @var $block \Magento\Reward\Block\Tooltip */
        $block = $objectManager->getObject(
            \Magento\Reward\Block\Tooltip\Checkout::class,
            [
                'data' => ['reward_type' => \Magento\Reward\Model\Action\Salesrule::class],
                'customerSession' => $customerSession,
                'rewardHelper' => $rewardHelper,
                'rewardInstance' => $rewardInstance,
                'storeManager' => $storeManager
            ]
        );
        $layout = $this->createMock(\Magento\Framework\View\Layout::class);

        $rewardHelper->expects($this->any())->method('isEnabledOnFront')->will($this->returnValue(true));

        $storeManager->expects($this->any())->method('getStore')->will($this->returnValue($store));
        $storeManager->getStore()->expects($this->any())->method('getWebsiteId')->will($this->returnValue(1));

        $rewardInstance->expects($this->any())->method('setCustomer')->will($this->returnValue($rewardInstance));
        $rewardInstance->expects($this->any())->method('setWebsiteId')->will($this->returnValue($rewardInstance));
        $rewardInstance->expects(
            $this->any()
        )->method(
            'getActionInstance'
        )->with(
            \Magento\Reward\Model\Action\Salesrule::class
        )->will(
            $this->returnValue($rewardAction)
        );

        $object = $block->setLayout($layout);
        $this->assertEquals($block, $object);
    }
}
