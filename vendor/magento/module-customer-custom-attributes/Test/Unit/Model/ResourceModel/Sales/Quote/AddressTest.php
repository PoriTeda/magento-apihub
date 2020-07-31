<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\ResourceModel\Sales\Quote;

class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address
     */
    protected $address;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parentResourceModelMock;

    protected function setUp()
    {
        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->parentResourceModelMock = $this->createMock(\Magento\Quote\Model\ResourceModel\Quote\Address::class);

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->resourceMock);

        $this->address = new \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address(
            $contextMock,
            $this->parentResourceModelMock
        );
    }

    public function testAttachDataToEntitiesNoItems()
    {
        $this->connectionMock->expects($this->never())
            ->method('select');
        $this->connectionMock->expects($this->never())
            ->method('fetchAll');

        $this->assertEquals($this->address, $this->address->attachDataToEntities([]));
    }

    public function testAttachDataToEntities()
    {
        $items = [];
        $itemIds = [];
        $rowSet = [];
        for ($i = 1; $i <= 3; $i++) {
            $row = ['entity_id' => $i, 'value' => $i];

            $item = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getId', 'addData']);
            $item->expects($this->exactly(2))
                ->method('getId')
                ->will($this->returnValue($i));
            $item->expects($this->once())
                ->method('addData')
                ->with($row);

            $items[] = $item;
            $itemIds[] = $i;
            $rowSet[] = $row;
        }

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->will($this->returnValue($selectMock));

        $selectMock->expects($this->once())
            ->method('from')
            ->with('magento_customercustomattributes_sales_flat_quote_address')
            ->will($this->returnSelf());
        $selectMock->expects($this->once())
            ->method('where')
            ->with("entity_id IN (?)", $itemIds)
            ->will($this->returnSelf());

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($selectMock)
            ->will($this->returnValue($rowSet));

        $this->assertEquals($this->address, $this->address->attachDataToEntities($items));
    }
}
