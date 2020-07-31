<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Index
     */
    protected $_index;

    /**
     * Store manager mock
     *
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_storeManager;

    /**
     * Session mock
     *
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_session;

    /**
     * TargetRule data helper mock
     *
     * @var \Magento\TargetRule\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_targetRuleData;

    /**
     * Index resource mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Index|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_resource;

    /**
     * Collection factory mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_collectionFactory;

    /**
     * Collection mock
     *
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_collection;

    protected function setUp()
    {
        $this->_storeManager = $this->_getCleanMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->_session = $this->_getCleanMock(\Magento\Customer\Model\Session::class);
        $this->_targetRuleData = $this->_getCleanMock(\Magento\TargetRule\Helper\Data::class);
        $this->_resource = $this->_getCleanMock(\Magento\TargetRule\Model\ResourceModel\Index::class);
        $this->_collectionFactory = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $this->_collection = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\Collection::class,
            ['addApplyToFilter', 'addProductFilter', 'addIsActiveFilter', 'setPriorityOrder', 'setFlag']
        );
        $this->_collection->expects($this->any())
            ->method('addApplyToFilter')
            ->will($this->returnSelf());

        $this->_collection->expects($this->any())
            ->method('addProductFilter')
            ->will($this->returnSelf());

        $this->_collection->expects($this->any())
            ->method('addIsActiveFilter')
            ->will($this->returnSelf());

        $this->_collection->expects($this->any())
            ->method('setPriorityOrder')
            ->will($this->returnSelf());

        $this->_collection->expects($this->any())
            ->method('setFlag')
            ->will($this->returnSelf());

        $this->_collectionFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->_collection));

        $this->_index = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Model\Index::class,
            [
                'context' => $this->_getCleanMock(\Magento\Framework\Model\Context::class),
                'registry' => $this->_getCleanMock(\Magento\Framework\Registry::class),
                'ruleFactory' => $this->_collectionFactory,
                'storeManager' => $this->_storeManager,
                'session' => $this->_session,
                'targetRuleData' => $this->_targetRuleData,
                'resource' => $this->_resource,
                'resourceCollection' => $this->_getCleanMock(\Magento\Framework\Data\Collection\AbstractDb::class)
            ]
        );
    }

    /**
     * Get clean mock by class name
     *
     * @param string $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getCleanMock($className)
    {
        return $this->createMock($className);
    }

    public function testSetType()
    {
        $this->_index->setType(1);
        $this->assertEquals(1, $this->_index->getType());
    }

    /**
     * Test get type
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The Catalog Product List Type needs to be defined. Verify the type and try again.
     */
    public function testGetType()
    {
        $this->_index->getType();
    }

    public function testSetStoreId()
    {
        $this->_index->setStoreId(1);
        $this->assertEquals(1, $this->_index->getStoreId());
    }

    public function testGetStoreId()
    {
        $store = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId', '__wakeup']);

        $store->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->_storeManager->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($store));

        $this->assertEquals(2, $this->_index->getStoreId());
    }

    public function testSetCustomerGroupId()
    {
        $this->_index->setCustomerGroupId(1);
        $this->assertEquals(1, $this->_index->getCustomerGroupId());
    }

    public function testGetCustomerGroupId()
    {
        $this->_session->expects($this->any())
            ->method('getCustomerGroupId')
            ->will($this->returnValue(2));

        $this->assertEquals(2, $this->_index->getCustomerGroupId());
    }

    public function testSetLimit()
    {
        $this->_index->setLimit(1);
        $this->assertEquals(1, $this->_index->getLimit());
    }

    public function testGetLimit()
    {
        $this->_index->setType(1);

        $this->_targetRuleData->expects($this->any())
            ->method('getMaximumNumberOfProduct')
            ->will($this->returnValue(2));

        $this->assertEquals(2, $this->_index->getLimit());
    }

    public function testSetProduct()
    {
        $object = $this->_getCleanMock(\Magento\Framework\DataObject::class);
        $this->_index->setProduct($object);
        $this->assertEquals($object, $this->_index->getProduct());
    }

    /**
     * Test getProduct
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Please define a product data object.
     */
    public function testGetProduct()
    {
        $object = $this->getMockForAbstractClass(\Magento\Catalog\Api\Data\ProductInterface::class);
        $this->_index->setData('product', $object);
        $this->assertEquals($object, $this->_index->getProduct());
    }

    public function testSetExcludeProductIds()
    {
        $productIds = 1;
        $this->_index->setExcludeProductIds($productIds);
        $this->assertEquals([$productIds], $this->_index->getExcludeProductIds());

        $productIds = [1, 2];
        $this->_index->setExcludeProductIds($productIds);
        $this->assertEquals($productIds, $this->_index->getExcludeProductIds());
    }

    public function testGetExcludeProductIds()
    {
        $productIds = 1;
        $this->_index->setData('exclude_product_ids', $productIds);
        $this->assertEquals([], $this->_index->getExcludeProductIds());

        $productIds = [1, 2];
        $this->_index->setData('exclude_product_ids', $productIds);
        $this->assertEquals($productIds, $this->_index->getExcludeProductIds());
    }

    public function testGetProductIds()
    {
        $productIds = [1, 2];
        $this->_resource->expects($this->any())
            ->method('getProductIds')
            ->will($this->returnValue($productIds));

        $this->assertEquals($productIds, $this->_index->getProductIds());
    }

    public function testGetRuleCollection()
    {
        $this->_index->setType(1);
        $object = $this->_getCleanMock(\Magento\Framework\DataObject::class);
        $this->_index->setData('product', $object);
        $this->assertEquals($this->_collection, $this->_index->getRuleCollection());
    }

    public function testSelect()
    {
        $select = $this->_getCleanMock(\Magento\Framework\DB\Select::class);
        $this->_resource->expects($this->any())
            ->method('select')
            ->will($this->returnValue($select));

        $this->assertEquals($select, $this->_index->select());
    }
}
