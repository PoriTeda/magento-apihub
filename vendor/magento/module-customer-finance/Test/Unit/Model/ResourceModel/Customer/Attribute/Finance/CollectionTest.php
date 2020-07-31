<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerFinance\Test\Unit\Model\ResourceModel\Customer\Attribute\Finance;

/**
 * Test class for \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Returns mock for finance collection
     *
     * @return \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection
     */
    protected function _getFinanceCollectionMock()
    {
        return $this->createPartialMock(
            \Magento\CustomerFinance\Model\ResourceModel\Customer\Attribute\Finance\Collection::class,
            ['getItems']
        );
    }

    /**
     * Test setOrder method
     */
    public function testSetOrder()
    {
        $collection = $this->_getFinanceCollectionMock();

        $first = new \Magento\Framework\DataObject(['id' => 9]);
        $second = new \Magento\Framework\DataObject(['id' => 10]);

        $collection->addItem($first);
        $collection->addItem($second);

        $collection->expects($this->at(0))->method('getItems')->willReturn([$first, $second]);
        $collection->expects($this->at(1))->method('getItems')->willReturn([$second, $first]);

        /** @var $orderFirst \Magento\Framework\DataObject */
        /** @var $orderSecond \Magento\Framework\DataObject */

        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        list($orderFirst, $orderSecond) = array_values($collection->getItems());
        $this->assertEquals($first->getId(), $orderFirst->getId());
        $this->assertEquals($second->getId(), $orderSecond->getId());

        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);
        list($orderFirst, $orderSecond) = array_values($collection->getItems());
        $this->assertEquals($second->getId(), $orderFirst->getId());
        $this->assertEquals($first->getId(), $orderSecond->getId());
    }

    /**
     * Test compare attributes method
     */
    public function testCompareAttributes()
    {
        $collection = $this->_getFinanceCollectionMock();
        $collection->setOrder('id');
        $first = new \Magento\Framework\DataObject(['id' => 9]);
        $second = new \Magento\Framework\DataObject(['id' => 10]);

        $this->assertLessThan(0, $collection->compareAttributes($first, $second));
        $this->assertGreaterThan(0, $collection->compareAttributes($second, $first));
        $this->assertEquals(0, $collection->compareAttributes($first, $first));
    }
}
