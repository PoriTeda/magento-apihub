<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions\Row as PermissionsRow;
use Magento\CatalogPermissions\Model\Permission;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Indexer\IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerMock;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appConfigMock;

    /**
     * @var \Magento\Framework\AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authorizationMock;

    /**
     * @var \Magento\CatalogPermissions\Model\PermissionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionFactoryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Permission|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Category
     */
    protected $category;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var int
     */
    protected $categoryId = 10;

    protected function setUp()
    {
        $this->indexerMock = $this->createPartialMock(
            \Magento\Indexer\Model\Indexer::class,
            ['getId', 'load', 'isScheduled', 'reindexRow', 'reindexList']
        );

        $this->appConfigMock = $this->createPartialMock(
            \Magento\CatalogPermissions\App\Backend\Config::class,
            ['isEnabled']
        );

        $this->authorizationMock = $this->createPartialMock(\Magento\Framework\Authorization::class, ['isAllowed']);

        $this->permissionFactoryMock = $this->createPartialMock(
            \Magento\CatalogPermissions\Model\PermissionFactory::class,
            ['create']
        );

        $this->permissionMock = $this->createPartialMock(
            \Magento\CatalogPermissions\Model\Permission::class,
            ['load', 'getId', 'delete', 'addData', 'setCategoryId', 'save', '__wakeup']
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            \Magento\Framework\Indexer\IndexerRegistry::class,
            ['get']
        );

        $this->category = new \Magento\CatalogPermissions\Model\Indexer\Plugin\Category(
            $this->indexerRegistryMock,
            $this->appConfigMock,
            $this->authorizationMock,
            $this->permissionFactoryMock
        );
    }

    public function testAfterSaveNotAllowed()
    {
        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->will(
            $this->returnValue(false)
        );

        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->never())->method('hasData');
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(false));

        $this->indexerMock->expects(
            $this->once()
        )->method(
            'reindexRow'
        )->with(
            $this->categoryId
        )->will(
            $this->returnSelf()
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowed()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->will($this->returnValue(true));

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->will(
            $this->returnValue($this->getPermissionData(0))
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->will(
            $this->returnValue(true)
        );

        $this->permissionMock->expects($this->once())->method('load')->with(1)->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('addData')->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('setCategoryId')->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('save')->will($this->returnSelf());

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->permissionMock)
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowedWithoutLoad()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->will($this->returnValue(true));

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->will(
            $this->returnValue($this->getPermissionData(1))
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->will(
            $this->returnValue(true)
        );

        $this->permissionMock->expects($this->never())->method('load');
        $this->permissionMock->expects($this->once())->method('addData')->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('setCategoryId')->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('save')->will($this->returnSelf());

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->permissionMock)
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->category->afterSave($categoryMock);
    }

    public function testAfterSaveAllowedDeletePermission()
    {
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('hasData')->with('permissions')->will($this->returnValue(true));

        $categoryMock->expects(
            $this->exactly(2)
        )->method(
            'getData'
        )->with(
            'permissions'
        )->will(
            $this->returnValue($this->getPermissionData(2))
        );

        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->authorizationMock->expects(
            $this->once()
        )->method(
            'isAllowed'
        )->with(
            'Magento_CatalogPermissions::catalog_magento_catalogpermissions'
        )->will(
            $this->returnValue(true)
        );

        $this->permissionMock->expects($this->once())->method('load')->with(1)->will($this->returnSelf());
        $this->permissionMock->expects($this->once())->method('getId')->will($this->returnValue(1));
        $this->permissionMock->expects($this->once())->method('delete');

        $this->permissionMock->expects($this->never())->method('addData')->will($this->returnSelf());

        $this->permissionFactoryMock->expects(
            $this->once()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->permissionMock)
        );

        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));

        $this->category->afterSave($categoryMock);
    }

    protected function getPermissionData($index)
    {
        $data = [
            [
                [
                    'id' => 1,
                    'website_id' => PermissionsRow::FORM_SELECT_ALL_VALUES,
                    'customer_group_id' => PermissionsRow::FORM_SELECT_ALL_VALUES,
                ],
            ],
            [['website_id' => 1, 'customer_group_id' => PermissionsRow::FORM_SELECT_ALL_VALUES]],
            [['id' => 1, '_deleted' => true]],
        ];

        return $data[$index];
    }

    public function testAroundMove()
    {
        $parentId = 15;
        $categoryMock = $this->getCategory();
        $categoryMock->expects($this->once())->method('getParentId')->will($this->returnValue($parentId));
        $closure = function () {
            return 'Expected';
        };
        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
            ->will($this->returnValue($this->indexerMock));
        $this->indexerMock->expects($this->once())->method('isScheduled')->will($this->returnValue(false));
        $this->indexerMock->expects($this->once())->method('reindexList')->with([$this->categoryId, $parentId]);

        $this->category->aroundMove($categoryMock, $closure, 0, 0);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Category
     */
    protected function getCategory()
    {
        $categoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\Category::class,
            ['hasData', 'getData', 'getId', 'getParentId', '__wakeup']
        );
        $categoryMock->expects($this->any())->method('getId')->will($this->returnValue($this->categoryId));
        return $categoryMock;
    }
}
