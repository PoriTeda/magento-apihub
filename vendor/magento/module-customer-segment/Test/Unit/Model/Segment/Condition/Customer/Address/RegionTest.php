<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer\Address;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RegionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Region
     */
    protected $subject;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceSegmentMock;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->resourceSegmentMock = $this->createPartialMock(
            \Magento\CustomerSegment\Model\ResourceModel\Segment::class,
            ['createSelect', 'getConnection']
        );

        $this->selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['from', 'where', 'limit']);

        $this->resourceSegmentMock->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->selectMock);

        $this->connectionMock = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\AdapterInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCheckSql']
        );

        $this->resourceSegmentMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->eavConfigMock = $this->createPartialMock(\Magento\Eav\Model\Config::class, ['getAttribute']);

        $this->attributeMock = $this->createPartialMock(
            \Magento\Eav\Model\Entity\Attribute::class,
            ['isStatic', 'getBackendTable', 'getAttributeCode', 'getId']
        );

        $this->attributeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->attributeMock->expects($this->any())
            ->method('getBackendTable')
            ->willReturn('data_table');

        $this->attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('region');

        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->with('customer_address', 'region')
            ->willReturn($this->attributeMock);

        $this->subject = $objectManager->getObject(
            \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\Region::class,
            [
                'resourceSegment' => $this->resourceSegmentMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );

        $this->subject->setData('value', '1');
    }

    /**
     * @param bool $customer
     * @param bool $isFiltered
     * @dataProvider getConditionsSqlDataProvider
     * @return void
     */
    public function testGetConditionsSql($customer, $isFiltered)
    {
        if (!$customer && $isFiltered) {
            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['' => new \Zend_Db_Expr('dual')], [new \Zend_Db_Expr(0)]);

            $this->selectMock->expects($this->never())
                ->method('where');

            $this->selectMock->expects($this->never())
                ->method('limit');
        } else {
            $column = 'region';
            $isNullCondition = "IF(caev.region IS NULL, 0, 1)";

            $this->connectionMock->expects($this->once())
                ->method('getCheckSql')
                ->with("caev.$column IS NULL", 0, 1)
                ->willReturn($isNullCondition);

            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['caev' => 'data_table'], $isNullCondition)
                ->willReturnSelf();

            $this->selectMock->expects($this->once())
                ->method('where')
                ->with("caev.entity_id = customer_address.entity_id");

            if ($isFiltered) {
                $this->selectMock->expects($this->once())
                    ->method('limit')
                    ->with(1);
            }
        }

        $this->subject->getConditionsSql($customer, 1, $isFiltered);
    }

    /**
     * @return array
     */
    public function getConditionsSqlDataProvider()
    {
        return [
            [false, true],
            [true, true],
            [true, false]
        ];
    }
}
