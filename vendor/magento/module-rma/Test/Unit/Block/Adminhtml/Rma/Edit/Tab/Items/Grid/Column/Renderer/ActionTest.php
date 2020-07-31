<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action
 */
class ActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action
     */
    protected $action;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $columnMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->columnMock = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid\Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getActions'])
            ->getMock();
        $this->action = $objectManager->getObject(
            \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action::class,
            []
        );
    }

    public function testRenderNoActions()
    {
        $this->columnMock->expects($this->once())
            ->method('getActions')
            ->will($this->returnValue(''));
        $row = new \Magento\Framework\DataObject();
        $this->action->setColumn($this->columnMock);
        $this->assertEquals('&nbsp;', $this->action->render($row));
    }

    public function testRender()
    {
        $actions = [['caption' => 'Details']];
        $this->columnMock->expects($this->once())
            ->method('getActions')
            ->will($this->returnValue($actions));
        $row = new \Magento\Framework\DataObject();
        $row->setStatus(\Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED);
        $this->action->setColumn($this->columnMock);
        $result = $this->action->render($row);
        $result = explode('<input', $result);
        $this->assertTrue(isset($result[2]));
        $this->assertContains('rma-action-links', $result[2]);
    }
}
