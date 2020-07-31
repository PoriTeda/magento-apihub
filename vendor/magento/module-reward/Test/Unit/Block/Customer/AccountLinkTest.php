<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Block\Customer;

class AccountLinkTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->_objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    public function testToHtml()
    {
        /** @var \Magento\Reward\Helper\Data|\PHPUnit_Framework_MockObject_MockObject $helper */
        $helper = $this->getMockBuilder(\Magento\Reward\Helper\Data::class)->disableOriginalConstructor()->getMock();

        /** @var \Magento\Reward\Block\Customer\AccountLink $block */
        $block = $this->_objectManagerHelper->getObject(
            \Magento\Reward\Block\Customer\AccountLink::class,
            ['rewardHelper' => $helper]
        );

        $helper->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(false));

        $this->assertEquals('', $block->toHtml());
    }
}
