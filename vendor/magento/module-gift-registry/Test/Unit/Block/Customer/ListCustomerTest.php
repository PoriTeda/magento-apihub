<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Block\Customer;

class ListCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Block\Customer\ListCustomer
     */
    protected $block;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDateMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->localeDateMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $this->contextMock
            ->expects($this->any())
        ->method('getLocaleDate')
        ->will($this->returnValue($this->localeDateMock));
        $this->block = $helper->getObject(
            \Magento\GiftRegistry\Block\Customer\ListCustomer::class,
            ['context' => $this->contextMock]
        );
    }

    public function testGetFormattedDate()
    {
        $date = '07/24/14';
        $itemMock = $this->createPartialMock(\Magento\GiftRegistry\Model\Entity::class, ['getCreatedAt', '__wakeup']);
        $itemMock->expects($this->once())->method('getCreatedAt')->will($this->returnValue($date));
        $this->localeDateMock
            ->expects($this->once())
            ->method('formatDateTime')
            ->with(new \DateTime($date), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE)
            ->will($this->returnValue($date));
        $this->assertEquals($date, $this->block->getFormattedDate($itemMock));
    }
}
