<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Plugin;

use Magento\GiftRegistry\Model\Plugin\OrderAddressName as OrderAddressNamePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\Order\Address as OrderAddress;

class OrderAddressNameTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderAddressNamePlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockBuilder(OrderAddress::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGiftregistryItemId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(OrderAddressNamePlugin::class);
    }

    public function testAfterGetName()
    {
        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn(1);

        $this->assertEquals(
            __('Ship to the recipient\'s address.'),
            $this->plugin->afterGetName($this->subjectMock, 'Result')
        );
    }

    public function testAfterGetNameNotGiftRegistry()
    {
        $result = 'result';

        $this->subjectMock->expects(static::atLeastOnce())
            ->method('getGiftregistryItemId')
            ->willReturn(null);

        $this->assertEquals($result, $this->plugin->afterGetName($this->subjectMock, $result));
    }
}
