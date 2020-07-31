<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Block\Adminhtml\Catalog\Category\Tab;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PermissionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions
     */
    protected $model;

    /**
     * @var \Magento\Backend\Block\Template\Context
     */
    protected $context;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $categoryTree;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\CatalogPermissions\Model\Permission\IndexFactory
     */
    protected $permIndexFactory;

    /**
     * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory
     */
    protected $permissionCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var \Magento\CatalogPermissions\Helper\Data
     */
    protected $catalogPermData;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $this->context = $this->createPartialMock(
            \Magento\Backend\Block\Template\Context::class,
            ['getStoreManager', 'getRequest']
        );

        $this->context->expects($this->any())->method('getStoreManager')->will(
            $this->returnValue($this->storeManagerMock)
        );

        $this->context->expects($this->any())->method('getRequest')->will(
            $this->returnValue($this->requestMock)
        );

        $this->categoryTree = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\Tree::class);

        $this->registry = $this->createPartialMock(\Magento\Framework\Registry::class, ['registry']);

        $this->categoryFactory = $this->createPartialMock(\Magento\Catalog\Model\CategoryFactory::class, ['create']);

        $this->jsonEncoder = $this->createMock(\Magento\Framework\Json\EncoderInterface::class);

        $this->permIndexFactory = $this->createPartialMock(
            \Magento\CatalogPermissions\Model\Permission\IndexFactory::class,
            ['create', 'getIndexForCategory']
        );

        $this->permissionCollectionFactory = $this->createPartialMock(
            \Magento\CatalogPermissions\Model\ResourceModel\Permission\CollectionFactory::class,
            ['create']
        );

        $this->groupCollectionFactory = $this->createPartialMock(
            \Magento\Customer\Model\ResourceModel\Group\CollectionFactory::class,
            ['create', 'getAllIds']
        );

        $this->catalogPermData = $this->createMock(\Magento\CatalogPermissions\Helper\Data::class);

        $this->model = new \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions(
            $this->context,
            $this->categoryTree,
            $this->registry,
            $this->categoryFactory,
            $this->jsonEncoder,
            $this->permIndexFactory,
            $this->permissionCollectionFactory,
            $this->groupCollectionFactory,
            $this->catalogPermData
        );
    }

    /**
     * @param int $categoryId
     * @param array $index
     * @param array $groupIds
     * @param array $result
     * @dataProvider getParentPermissionsDataProvider
     */
    public function testGetParentPermissions($categoryId, $index, $groupIds, $result)
    {
        $categoryMock = $this->createPartialMock(\Magento\Catalog\Model\Category::class, ['getId', 'getParentId']);

        $websiteMock = $this->createPartialMock(\Magento\Store\Model\Website::class, ['getId', 'getDefaultStore']);

        $categoryMock->expects($this->any())->method('getId')->will($this->returnValue($categoryId));
        $categoryMock->expects($this->any())->method('getParentId')->will($this->returnValue(1));
        $websiteMock->expects($this->any())->method('getId')->will($this->returnValue(1));
        $websiteMock->expects($this->any())->method('getDefaultStore')->will($this->returnValue(1));

        $this->registry->expects($this->any())->method('registry')->will($this->returnValue($categoryMock));
        $this->permIndexFactory->expects($this->any())->method('create')->will($this->returnSelf());
        $this->permIndexFactory->expects($this->any())->method('getIndexForCategory')->will($this->returnValue($index));
        $this->catalogPermData->expects($this->any())->method('isAllowedCategoryView')->will($this->returnValue(true));
        $this->catalogPermData->expects($this->any())->method('isAllowedProductPrice')->will($this->returnValue(true));
        $this->catalogPermData->expects($this->any())->method('isAllowedCheckoutItems')->will($this->returnValue(true));
        $this->groupCollectionFactory->expects($this->any())->method('create')->will($this->returnSelf());
        $this->groupCollectionFactory->expects($this->any())->method('getAllIds')->will($this->returnValue($groupIds));
        $this->requestMock->expects($this->any())->method('getParam')->will($this->returnValue(1));
        $this->storeManagerMock->expects($this->any())->method('getWebsites')->will(
            $this->returnValue([$websiteMock])
        );
        $this->assertEquals($result, $this->model->getParentPermissions());
    }

    /**
     * @return array
     */
    public function getParentPermissionsDataProvider()
    {
        $index = [
            1 => [
                'website_id' => 1,
                'customer_group_id' => 1,
                'grant_catalog_category_view' => '0',
                'grant_catalog_product_price' => '-1',
                'grant_checkout_items' => '-2'
            ],
            2 => [
                'website_id' => 2,
                'customer_group_id' => 2,
                'grant_catalog_category_view' => '-1',
                'grant_catalog_product_price' => '-2',
                'grant_checkout_items' => '0'
            ]
        ];
        $groupIds = [1, 2];
        $groupIdsSecond = [1, 2, 3];
        $result = [
            '1_1' => ['category' => '-1', 'product' => '-1', 'checkout' => '-2'],
            '2_2' => ['category' => '-1', 'product' => '-2', 'checkout' => '0'],
            '1_2' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1']
        ];
        $resultSecond = [
            '1_1' => ['category' => '-1', 'product' => '-1', 'checkout' => '-2'],
            '2_2' => ['category' => '-1', 'product' => '-2', 'checkout' => '0'],
            '1_2' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1'],
            '1_3' => ['category' => '-1', 'product' => '-1', 'checkout' => '-1']
        ];
        return [[3, $index, $groupIds, $result], [0, $index, $groupIdsSecond, $resultSecond]];
    }
}
