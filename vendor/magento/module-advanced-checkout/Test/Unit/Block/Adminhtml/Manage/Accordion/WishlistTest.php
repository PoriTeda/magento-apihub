<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Test\Unit\Block\Adminhtml\Manage\Accordion;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Wishlist accordion test
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WishlistTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Wishlist */
    protected $wishlist;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Backend\Block\Template\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextMock;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registryMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $itemCollectionMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockItemMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistry;

    protected function setUp()
    {
        $writeInterface = $this->createMock(\Magento\Framework\Filesystem\Directory\WriteInterface::class);
        $filesystem = $this->createMock(\Magento\Framework\Filesystem::class);
        $filesystem->expects($this->once())
            ->method('getDirectoryWrite')
            ->with($this->equalTo(DirectoryList::VAR_DIR))
            ->will($this->returnValue($writeInterface));

        $this->contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $this->contextMock->expects($this->once())
            ->method('getFilesystem')
            ->will($this->returnValue($filesystem));

        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);

        $this->itemCollectionMock = $this->createMock(
            \Magento\Wishlist\Model\ResourceModel\Item\Collection::class
        );
        $itemCollFactory = $this->createPartialMock(
            \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory::class,
            ['create']
        );
        $itemCollFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($this->itemCollectionMock));

        $this->stockItemMock = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Stock\Item::class,
            ['getIsInStock', '__wakeup']
        );
        $this->stockItemMock->expects($this->any())
            ->method('getIsInStock')
            ->will($this->returnValue(true));

        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Model\StockRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem', '__wakeup'])
            ->getMock();
        $this->stockRegistry->expects($this->any())
            ->method('getStockItem')
            ->will($this->returnValue($this->stockItemMock));

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->wishlist = $this->objectManagerHelper->getObject(
            \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Wishlist::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->registryMock,
                'itemFactory' => $itemCollFactory,
                'stockRegistry' => $this->stockRegistry
            ]
        );
    }

    public function testGetItemsCollection()
    {
        $customerId = 2;
        $customer = $this->createMock(\Magento\Customer\Model\Customer::class);
        $customer->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($customerId));

        $storeIds = [1, 2, 3];
        $website = $this->createMock(\Magento\Store\Model\Website::class);
        $website->expects($this->once())
            ->method('getStoreIds')
            ->will($this->returnValue($storeIds));

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->once())
            ->method('getWebsite')
            ->will($this->returnValue($website));

        $this->registryMock->expects($this->any())
            ->method('registry')
            ->will($this->returnValueMap(
                [
                    ['checkout_current_customer', $customer],
                    ['checkout_current_store', $store],
                ]
            ));

        $this->itemCollectionMock->expects($this->once())
            ->method('addCustomerIdFilter')
            ->with($this->equalTo($customerId))
            ->will($this->returnSelf());

        $this->itemCollectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with($this->equalTo($storeIds))
            ->will($this->returnSelf());

        $this->itemCollectionMock->expects($this->once())
            ->method('setVisibilityFilter')
            ->will($this->returnSelf());

        $this->itemCollectionMock->expects($this->once())
            ->method('setSalableFilter')
            ->will($this->returnSelf());

        $this->itemCollectionMock->expects($this->once())
            ->method('resetSortOrder')
            ->will($this->returnSelf());

        $this->prepareItemListMock();

        $this->assertNull($this->wishlist->getData('items_collection'));
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getItemsCollection());
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getData('items_collection'));
        // lazy load test
        $this->assertSame($this->itemCollectionMock, $this->wishlist->getItemsCollection());
    }

    protected function prepareItemListMock()
    {
        $itemList = new \ArrayIterator(
            [
                $this->getWishlistItemMock(1, ['is_product' => false]),
                $this->getWishlistItemMock(
                    2,
                    [
                        'is_product' => true,
                        'product_id' => 22,
                        'service_stock' => false,
                        'product_stock' => true,
                    ]
                ),
                $this->getWishlistItemMock(
                    3,
                    [
                        'is_product' => true,
                        'product_id' => 33,
                        'service_stock' => true,
                        'product_stock' => false,
                    ]
                ),
                $this->getWishlistItemMock(
                    4,
                    [
                        'is_product' => true,
                        'product_id' => 44,
                        'service_stock' => true,
                        'product_stock' => true,
                        'product_name' => 'Product Name',
                        'product_price' => 'Product Price',
                    ]
                ),
            ]
        );

        $this->stockItemMock->expects($this->any())
            ->method('getIsInStock')
            ->will($this->returnValueMap(
                [
                    [22, false],
                    [33, true],
                    [44, true],
                ]
            ));
        $this->itemCollectionMock->expects($this->any())
            ->method('removeItemByKey')
            ->will($this->returnValueMap(
                [
                    [2, $this->itemCollectionMock],
                    [3, $this->itemCollectionMock],
                ]
            ));

        $this->itemCollectionMock->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue($itemList));
    }

    /**
     * @param int $itemId
     * @param array $config
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWishlistItemMock($itemId, $config)
    {
        $item = $this->createPartialMock(
            \Magento\Wishlist\Model\ResourceModel\Item::class,
            ['getId', 'setName', 'setPrice', 'getProduct', '__wakeup']
        );

        if ($config['is_product']) {
            $product = $this->createPartialMock(
                \Magento\Catalog\Model\Product::class,
                ['getId', '__wakeup', 'getName', 'getPrice', 'isInStock']
            );

            $item->expects($this->once())
                ->method('getProduct')
                ->will($this->returnValue($product));

            $product->expects($this->once())
                ->method('getId')
                ->will($this->returnValue($config['product_id']));

            if (!$config['service_stock'] || !$config['product_stock']) {
                $item->expects($this->once())
                    ->method('getId')
                    ->will($this->returnValue($itemId));
            }

            if ($config['service_stock']) {
                $product->expects($this->once())
                    ->method('isInStock')
                    ->will($this->returnValue($config['product_stock']));
            }
            if ($config['service_stock'] && $config['product_stock']) {
                $product->expects($this->once())
                    ->method('getName')
                    ->will($this->returnValue($config['product_name']));

                $product->expects($this->once())
                    ->method('getPrice')
                    ->will($this->returnValue($config['product_price']));

                $item->expects($this->once())
                    ->method('setName')
                    ->with($this->equalTo($config['product_name']))
                    ->will($this->returnSelf());
                $item->expects($this->once())
                    ->method('setPrice')
                    ->with($this->equalTo($config['product_price']))
                    ->will($this->returnSelf());
            }
        } else {
            $item->expects($this->once())
                ->method('getProduct')
                ->will($this->returnValue(false));
        }
        return $item;
    }
}
