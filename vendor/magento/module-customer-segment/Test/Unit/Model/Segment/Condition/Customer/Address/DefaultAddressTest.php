<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Model\Segment\Condition\Customer\Address;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class DefaultAddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\DefaultAddress
     */
    protected $subject;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceSegmentMock;

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
            ['createSelect']
        );

        $this->selectMock = $this->createPartialMock(\Magento\Framework\DB\Select::class, ['from', 'where', 'limit']);

        $this->resourceSegmentMock->expects($this->any())
            ->method('createSelect')
            ->willReturn($this->selectMock);

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
            ->willReturn('default_billing');

        $this->eavConfigMock->expects($this->any())
            ->method('getAttribute')
            ->with('customer', 'default_billing')
            ->willReturn($this->attributeMock);

        $this->subject = $objectManager->getObject(
            \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address\DefaultAddress::class,
            [
                'resourceSegment' => $this->resourceSegmentMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );

        $this->subject->setData('value', 'default_billing');
    }

    /**
     * @param bool $customer
     * @param bool $isFiltered
     * @param bool|null $isStaticAttribute
     * @dataProvider getConditionsSqlDataProvider
     * @return void
     */
    public function testGetConditionsSql($customer, $isFiltered, $isStaticAttribute)
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
            $this->attributeMock->expects($this->any())
                ->method('isStatic')
                ->willReturn($isStaticAttribute);

            $this->selectMock->expects($this->once())
                ->method('from')
                ->with(['default' => 'data_table'], [new \Zend_Db_Expr(1)]);

            if (!$isStaticAttribute) {
                $this->selectMock->expects($this->at(1))
                    ->method('where')
                    ->with("`default`.`attribute_id` = ?", 1)
                    ->willReturnSelf();

                $this->selectMock->expects($this->at(2))
                    ->method('where')
                    ->with("`default`.`value` = `customer_address`.`entity_id`");

                if ($isFiltered) {
                    $this->selectMock->expects($this->at(3))
                        ->method('where')
                        ->with("default.entity_id = :customer_id");
                }
            } else {
                $this->selectMock->expects($this->at(1))
                    ->method('where')
                    ->with("`default`.`default_billing` = `customer_address`.`entity_id`");

                if ($isFiltered) {
                    $this->selectMock->expects($this->at(2))
                        ->method('where')
                        ->with("default.entity_id = :customer_id");
                }
            }

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
            [false, true, null],
            [true, true, true],
            [true, false, true],
            [true, false, false]
        ];
    }
}
