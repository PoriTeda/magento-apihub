<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Accordion;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Test for Rcompared
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RcomparedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Rcompared
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compareList;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemCollection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $listCompareFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var int
     */
    protected $storeId = 1;

    /**
     * @var int
     */
    protected $customerId = 1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productListFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productCollection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistry;

    protected function setUp()
    {
        $this->itemCollection =
            $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();

        $this->listCompareFactory = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory::class,
            ['create']
        );
        $this->listCompareFactory->expects($this->any())->method('create')
            ->will($this->returnValue($this->itemCollection));

        $customer = $this->createMock(\Magento\Customer\Model\Customer::class);
        $customer->expects($this->any())->method('getId')->will($this->returnValue($this->customerId));
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())->method('getId')->will($this->returnValue($this->storeId));

        $this->registry = $this->createRegistryMock([
            'checkout_current_customer' => $customer,
            'checkout_current_store'    => $store,
        ]);

        $this->productCollection = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->productListFactory =
            $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->productListFactory->expects($this->any())->method('create')
            ->will($this->returnValue($this->productCollection));

        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Model\StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem', '__wakeup'])
            ->getMock();
    }

    /**
     * Create registry mock
     *
     * @param array $registryData
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRegistryMock($registryData)
    {
        $coreRegistry = $this->createMock(\Magento\Framework\Registry::class);
        $registryCallback = $this->returnCallback(function ($key) use ($registryData) {
            return $registryData[$key];
        });
        $coreRegistry->expects($this->any())->method('registry')->will($registryCallback);
        return $coreRegistry;
    }

    /**
     * Create mocks of product
     *
     * @return array
     */
    protected function createMocksOfProduct()
    {
        $firstProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isInStock', '__wakeup'])
            ->getMock();
        $firstProduct->expects($this->any())->method('getId')->will($this->returnValue(2));
        $firstProduct->expects($this->any())->method('isInStock')->will($this->returnValue(true));

        $secondProduct = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'isInStock', '__wakeup'])
            ->getMock();
        $secondProduct->expects($this->any())->method('getId')->will($this->returnValue(3));
        $secondProduct->expects($this->any())->method('isInStock')->will($this->returnValue(false));

        $this->productCollection->expects($this->once())->method('removeItemByKey')->with(3);

        $stockItem = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Stock\Item::class,
            ['getIsInStock', '__wakeup']
        );
        $stockItem->expects($this->any())
            ->method('getIsInStock')
            ->will($this->returnValue(true));

        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->will($this->returnValue($stockItem));

        return [$firstProduct, $secondProduct];
    }

    public function testItemsCollectionGetter()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->itemCollection->expects($this->once())->method('useProductItem')->will($this->returnSelf());
        $this->itemCollection->expects($this->once())->method('setStoreId')->with($this->storeId)
            ->will($this->returnSelf());
        $this->itemCollection->expects($this->once())->method('addStoreFilter')->with($this->storeId)
            ->will($this->returnSelf());
        $this->itemCollection->expects($this->once())->method('setCustomerId')->with($this->customerId)
            ->will($this->returnSelf());
        $this->itemCollection->expects($this->any())->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([])));

        $catalogConfig = $this->createMock(\Magento\Catalog\Model\Config::class);
        $catalogConfig->expects($this->any())->method('getProductAttributes')->will($this->returnValue([]));

        $this->productCollection->expects($this->once())->method('setStoreId')->with($this->storeId)
            ->will($this->returnSelf());
        $this->productCollection->expects($this->once())->method('addStoreFilter')->with($this->storeId)
            ->will($this->returnSelf());
        $this->productCollection->expects($this->once())->method('addAttributeToSelect')->with(['status'])
            ->will($this->returnSelf());
        $this->productCollection->expects($this->any())->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator($this->createMocksOfProduct())));
        $this->productCollection->expects($this->once())->method('addOptionsToResult')->will($this->returnSelf());
        $this->productCollection->method('getItems')->will($this->returnValue($this->createMocksOfProduct()));

        $adminhtmlSales = $this->createMock(\Magento\Sales\Helper\Admin::class);
        $adminhtmlSales->expects($this->once())->method('applySalableProductTypesFilter')
            ->will($this->returnValue($this->productCollection));

        $this->model = $objectManagerHelper->getObject(
            \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Rcompared::class,
            [
                'compareListFactory' => $this->listCompareFactory,
                'coreRegistry'       => $this->registry,
                'catalogConfig'      => $catalogConfig,
                'productListFactory' => $this->productListFactory,
                'adminhtmlSales'     => $adminhtmlSales,
                'stockRegistry'      => $this->stockRegistry
            ]
        );
        $this->assertSame($this->productCollection, $this->model->getData('items_collection'));
    }
}
