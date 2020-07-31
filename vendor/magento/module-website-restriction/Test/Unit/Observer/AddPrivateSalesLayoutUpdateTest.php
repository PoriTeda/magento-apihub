<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Test\Unit\Observer;

class AddPrivateSalesLayoutUpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Observer\AddPrivateSalesLayoutUpdate
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observer;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\Magento\WebsiteRestriction\Model\ConfigInterface::class);
        $this->updateMock = $this->createMock(\Magento\Framework\View\Layout\ProcessorInterface::class);
        $this->observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $layoutMock->expects($this->any())->method('getUpdate')->will($this->returnValue($this->updateMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getLayout']);
        $eventMock->expects($this->any())->method('getLayout')->will($this->returnValue($layoutMock));

        $this->observer->expects($this->any())->method('getEvent')->will($this->returnValue($eventMock));
        $this->model = new \Magento\WebsiteRestriction\Observer\AddPrivateSalesLayoutUpdate($this->configMock);
    }

    public function testExecuteSuccess()
    {
        $this->configMock->expects($this->once())->method('getMode')->will($this->returnValue(1));
        $this->updateMock->expects($this->once())->method('addHandle')->with('restriction_privatesales_mode');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithStrictType()
    {
        $this->configMock->expects($this->once())->method('getMode')->will($this->returnValue('1'));
        $this->updateMock->expects($this->never())->method('addHandle');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithNonAllowedMode()
    {
        $this->configMock->expects($this->once())->method('getMode')->will($this->returnValue('some mode'));
        $this->updateMock->expects($this->never())->method('addHandle');
        $this->model->execute($this->observer);
    }
}
