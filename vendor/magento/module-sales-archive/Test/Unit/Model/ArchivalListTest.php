<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Test\Unit\Model;

class ArchivalListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var $_model \Magento\SalesArchive\Model\ArchivalList
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_objectManagerMock;

    protected function setUp()
    {
        $this->_objectManagerMock = $this->createPartialMock(
            \Magento\Framework\ObjectManager\ObjectManager::class,
            ['get', 'create']
        );

        $this->_model = new \Magento\SalesArchive\Model\ArchivalList($this->_objectManagerMock);
    }

    /**
     * @dataProvider dataProviderGetResourcePositive
     * @param string $entity
     * @param string $className
     */
    public function testGetResourcePositive($entity, $className)
    {
        $this->_objectManagerMock->expects($this->once())->method('get')->will($this->returnArgument(0));
        $this->assertEquals($className, $this->_model->getResource($entity));
    }

    public function dataProviderGetResourcePositive()
    {
        return [
            ['order', \Magento\Sales\Model\ResourceModel\Order::class],
            ['invoice', \Magento\Sales\Model\ResourceModel\Order\Invoice::class],
            ['shipment', \Magento\Sales\Model\ResourceModel\Order\Shipment::class],
            ['creditmemo', \Magento\Sales\Model\ResourceModel\Order\Creditmemo::class]
        ];
    }

    public function testGetResourceNegative()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('FAKE!ENTITY entity isn\'t allowed');
        $this->_model->getResource('FAKE!ENTITY');
    }

    /**
     * @dataProvider dataGetEntityByObject
     * @param string|bool $entity
     * @param string $className
     */
    public function testGetEntityByObject($entity, $className)
    {
        $object = $this->createMock($className);
        $this->assertEquals($entity, $this->_model->getEntityByObject($object));
    }

    public function dataGetEntityByObject()
    {
        return [
            ['order', \Magento\Sales\Model\ResourceModel\Order::class],
            ['invoice', \Magento\Sales\Model\ResourceModel\Order\Invoice::class],
            ['shipment', \Magento\Sales\Model\ResourceModel\Order\Shipment::class],
            ['creditmemo', \Magento\Sales\Model\ResourceModel\Order\Creditmemo::class],
            [false, \Magento\Framework\DataObject::class]
        ];
    }
}
