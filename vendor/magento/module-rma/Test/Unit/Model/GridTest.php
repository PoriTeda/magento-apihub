<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model;

class GridTest extends \PHPUnit\Framework\TestCase
{
    const TEST_STATUS = 'test_pending';

    /**
     * @var \Magento\Rma\Model\Grid
     */
    protected $rmaGrid;

    /**
     * @var \Magento\Rma\Model\Rma\Source\StatusFactory|\PHPUnit_Framework_MockObject
     */
    protected $statusFactoryMock;

    /**
     * @var \Magento\Framework\Model\Context|\PHPUnit_Framework_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Model\ResourceModel\AbstractResource|\PHPUnit_Framework_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb|\PHPUnit_Framework_MockObject
     */
    protected $resourceCollectionMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\Model\Context::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->statusFactoryMock = $this->createPartialMock(
            \Magento\Rma\Model\Rma\Source\StatusFactory::class,
            ['create']
        );
        $this->resourceMock = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName'])
            ->getMockForAbstractClass();
        $this->resourceCollectionMock = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = ['status' => static::TEST_STATUS];
        $this->rmaGrid = new \Magento\Rma\Model\Grid(
            $this->contextMock,
            $this->registryMock,
            $this->statusFactoryMock,
            $this->resourceMock,
            $this->resourceCollectionMock,
            $data
        );
    }

    public function testGetStatusLabel()
    {
        $sourceStatus = $this->createPartialMock(\Magento\Rma\Model\Rma\Source\Status::class, ['getItemLabel']);
        $this->statusFactoryMock->expects($this->once())->method('create')->will($this->returnValue($sourceStatus));
        $sourceStatus->expects($this->any())
            ->method('getItemLabel')
            ->willReturn(static::TEST_STATUS);

        $this->assertEquals(static::TEST_STATUS, $this->rmaGrid->getStatusLabel());
    }
}
