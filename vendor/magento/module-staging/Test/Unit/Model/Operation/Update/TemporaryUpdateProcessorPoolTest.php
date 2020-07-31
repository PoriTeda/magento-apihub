<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Staging\Model\Operation\Update\TemporaryUpdateProcessorPool;

class TemporaryUpdateProcessorPoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TemporaryUpdateProcessorPool
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $objManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $processorMock;

    protected function setUp()
    {
        $this->objManagerMock = $this->createMock(\Magento\Framework\App\ObjectManager::class);
        $this->processorMock = $this->createMock(
            \Magento\Staging\Model\Operation\Update\UpdateProcessorInterface::class
        );
        $processors = [
            'default' => 'DefaultProcessorMock',
            'NewEntityType' => 'NewEntityTypeMock'
        ];
        $this->model = new TemporaryUpdateProcessorPool($this->objManagerMock, $processors);
    }

    public function testGetProcessor()
    {
        $this->objManagerMock
            ->expects($this->once())
            ->method('get')
            ->with('NewEntityTypeMock')
            ->willReturn($this->processorMock);
        $this->assertEquals($this->processorMock, $this->model->getProcessor('NewEntityType'));
    }

    public function testGetDefaultProcessor()
    {
        $this->objManagerMock
            ->expects($this->once())
            ->method('get')
            ->with('DefaultProcessorMock')
            ->willReturn($this->processorMock);
        $this->assertEquals($this->processorMock, $this->model->getProcessor('EntityWithoutProcessor'));
    }
}
