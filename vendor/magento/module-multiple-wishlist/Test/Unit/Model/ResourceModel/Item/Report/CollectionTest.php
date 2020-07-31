<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Model\ResourceModel\Item\Report;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\MultipleWishlist\Model\ResourceModel\Item\Report\Collection
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerResourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * Test method _addCustomerInfo throw constructor
     */
    public function testAddCustomerInfo()
    {
        $joinCustomerData = ['customer' => 'customer_entity'];
        $joinCustomerMap = 'customer.entity_id = wishlist_table.customer_id';

        $this->selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock->expects($this->any())
            ->method('from')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('reset')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->any())
            ->method('join')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('joinLeft')
            ->with($joinCustomerData, $joinCustomerMap, [])
            ->willReturnSelf();

        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selectMock->expects($this->any())
            ->method('getConnection')
            ->withAnyParameters()
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);
        $this->resourceMock = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())
            ->method('getMainTable')
            ->willReturn('test_table');
        $this->resourceMock->expects($this->any())
            ->method('getTable')
            ->willReturn('customer_entity');

        $this->customerResourceMock = $this->getMockBuilder(\Magento\Customer\Model\ResourceModel\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldsetConfigmock = $this->getMockBuilder(\Magento\Framework\DataObject\Copy\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldsetConfigmock->expects($this->once())
            ->method('getFieldset')
            ->with('customer_account')
            ->willReturn([]);

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $helper->getObject(
            \Magento\MultipleWishlist\Model\ResourceModel\Item\Report\Collection::class,
            [
                'customerResource' => $this->customerResourceMock,
                'resource' => $this->resourceMock,
                'fieldsetConfig' => $fieldsetConfigmock,
            ]
        );
    }
}
