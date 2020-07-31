<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update;

use Magento\Staging\Block\Adminhtml\Update\Upcoming;
use Magento\Framework\AuthorizationInterface;

class UpcomingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authorization;

    /**
     * @var Upcoming
     */
    protected $block;

    protected function setUp()
    {
        $this->entityProviderMock = $this->createMock(
            \Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface::class
        );

        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $this->contextMock->expects($this->once())->method('getLayout')->willReturn($this->layoutMock);
        $this->authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);

        $this->block = new Upcoming(
            $this->contextMock,
            $this->entityProviderMock,
            $this->authorization
        );
    }

    public function testToHtmlNoId()
    {
        $this->entityProviderMock->expects($this->once())->method('getId')->willReturn(null);
        $this->layoutMock->expects($this->never())->method('getChildNames');
        $this->assertEmpty($this->block->toHtml());
    }

    public function testToHtml()
    {
        $rendered = 'hop';

        $this->entityProviderMock->expects($this->once())->method('getId')->willReturn(123);
        $this->layoutMock->expects($this->atLeastOnce())->method('getChildNames')->willReturn([1]);
        $this->layoutMock->expects($this->atLeastOnce())->method('renderElement')->willReturn($rendered);

        $this->assertEquals($rendered, $this->block->toHtml());
    }
}
