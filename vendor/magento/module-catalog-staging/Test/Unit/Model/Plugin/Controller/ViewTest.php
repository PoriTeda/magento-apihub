<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\Controller;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogStaging\Model\Plugin\Controller\View;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\DB\Select;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $versionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $previewMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $tableResolver;

    /**
     * @var View
     */
    protected $model;

    protected function setUp()
    {
        $this->versionManagerMock = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->previewMock = $this->getMockBuilder(
            \Magento\CatalogStaging\Model\Indexer\Category\Product\Preview::class
        )->disableOriginalConstructor()->getMock();
        $this->categoryRepositoryMock = $this->getMockBuilder(\Magento\Catalog\Api\CategoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->setMethods([
                'getId',
            ])
            ->getMockForAbstractClass();
        $store->expects($this->any())
            ->method('getId')
            ->willReturn(0);
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods([
                'getStore',
            ])
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);
        $objectManager = new ObjectManager($this);

        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->setMethods([
                'getConnection',
                'getTableName'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $resource->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $resource->expects($this->any())->method('getTableName')->willReturnArgument(0);

        $this->tableResolver = $objectManager->getObject(
            \Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver::class,
            [
                'resource' => $resource
            ]
        );

        $this->model = $objectManager->getObject(
            View::class,
            [
                'versionManager' => $this->versionManagerMock,
                'resourceConnection' => $this->resourceConnectionMock,
                'preview' => $this->previewMock,
                'categoryRepository' => $this->categoryRepositoryMock,
                'storeManager' => $this->storeManager,
                'tableResolver' => $this->tableResolver
            ]
        );
    }

    public function testBeforeExecuteNotPreview()
    {
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(false);
        $viewMock->expects($this->never())
            ->method('getRequest');

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecuteNoCategory()
    {
        $categoryId = null;

        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn(null);

        $this->model->beforeExecute($viewMock);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Table catalog_category_product_index_store0 already mapped
     */
    public function testBeforeExecuteAlreadyMapped()
    {
        $categoryId = 1;
        $allIds = [1, 2, 3];
        $indexTableTmp = 'index_tmp';
        $selectFromData = [
            'main_table' => [],
            'cat_index' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['cat_index']['joinType'] = Select::LEFT_JOIN;

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->once())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);
        $selectMock->expects($this->once())
            ->method('setPart')
            ->with(Select::FROM, $expectedSelectFromData);

        $categoryMock->expects($this->once())
            ->method('getProductCollection')
            ->willReturn($productCollectionMock);
        $productCollectionMock->expects($this->once())
            ->method('addCategoryFilter')
            ->with($categoryMock)
            ->willReturnSelf();
        $productCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $productCollectionMock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

        $this->previewMock->expects($this->once())
            ->method('execute')
            ->with($categoryId, $allIds);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with($indexTableTmp)
            ->willReturnArgument(0);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn($indexTableTmp);

        $this->model->beforeExecute($viewMock);
    }

    public function testBeforeExecute()
    {
        $categoryId = 1;
        $allIds = [1, 2, 3];
        $indexTableTmp = 'index_tmp';
        $selectFromData = [
            'main_table' => [],
            'cat_index' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['cat_index']['joinType'] = Select::LEFT_JOIN;

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->once())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);
        $selectMock->expects($this->once())
            ->method('setPart')
            ->with(Select::FROM, $expectedSelectFromData);

        $categoryMock->expects($this->once())
            ->method('getProductCollection')
            ->willReturn($productCollectionMock);
        $productCollectionMock->expects($this->once())
            ->method('addCategoryFilter')
            ->with($categoryMock)
            ->willReturnSelf();
        $productCollectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);
        $viewMock = $this->getMockBuilder(\Magento\Catalog\Controller\Category\View::class)
            ->disableOriginalConstructor()
            ->getMock();
        $viewMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willReturn($categoryId);
        $this->categoryRepositoryMock->expects($this->once())
            ->method('get')
            ->with($categoryId)
            ->willReturn($categoryMock);

        $productCollectionMock->expects($this->once())
            ->method('getAllIds')
            ->willReturn($allIds);

        $this->previewMock->expects($this->once())
            ->method('execute')
            ->with($categoryId, $allIds);
        $this->previewMock->expects($this->once())
            ->method('getTemporaryTable')
            ->willReturn($indexTableTmp);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getTableName')
            ->with($indexTableTmp)
            ->willReturnArgument(0);
        $this->resourceConnectionMock->expects($this->once())
            ->method('getMappedTableName')
            ->with('catalog_category_product_index_store0')
            ->willReturn(false);

        $this->resourceConnectionMock->expects($this->once())
            ->method('setMappedTableName')
            ->with('catalog_category_product_index_store0', $indexTableTmp);

        $this->model->beforeExecute($viewMock);
    }
}
