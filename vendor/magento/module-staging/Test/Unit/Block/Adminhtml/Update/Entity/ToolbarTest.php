<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

use Magento\Staging\Block\Adminhtml\Update\Entity\Toolbar;

class ToolbarTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var Toolbar
     */
    protected $toolbar;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $buttonListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $toolbarMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Backend\Block\Widget\Context::class);

        $this->buttonListMock = $this->createMock(\Magento\Backend\Block\Widget\Button\ButtonList::class);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getButtonList')
            ->willReturn($this->buttonListMock);

        $this->toolbarMock = $this->createMock(\Magento\Backend\Block\Widget\Button\ToolbarInterface::class);
        $this->contextMock->expects($this->once())
            ->method('getButtonToolbar')
            ->willReturn($this->toolbarMock);
        $this->data = [];
        $this->toolbar = new Toolbar(
            $this->contextMock,
            $this->data
        );
    }

    public function testUpdateButton()
    {
        $buttonId = '123';
        $key = '456';
        $data = '2341';
        $this->buttonListMock->expects($this->once())
            ->method('update')
            ->with($buttonId, $key, $data);
        $this->toolbar->updateButton($buttonId, $key, $data);
    }

    public function testPrepareLayout()
    {
        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $this->buttonListMock->expects($this->once())->method('add');
        $this->toolbarMock->expects($this->once())->method('pushButtons');
        $this->toolbar->setLayout($layoutMock);
    }

    public function testAddButton()
    {
        $buttonId = 'LuckyId';
        $data = [300, 20, 30];
        $level = 100;
        $sortOrder = 330;
        $region = 'SomePlace';

        $this->buttonListMock->expects($this->once())
            ->method('add')
            ->with($buttonId, $data, $level, $sortOrder, $region);
        $this->toolbar->addButton($buttonId, $data, $level, $sortOrder, $region);
    }

    public function testRemoveButton()
    {
        $buttonId = 'HopHey';

        $this->buttonListMock->expects($this->once())
            ->method('remove')
            ->with($buttonId);
        $this->toolbar->removeButton($buttonId);
    }

    public function testCanRender()
    {
        $itemMock = $this->createMock(\Magento\Backend\Block\Widget\Button\Item::class);
        $itemMock->expects($this->once())->method('isDeleted')->willReturn(true);
        $this->assertEquals(false, $this->toolbar->canRender($itemMock));
    }
}
