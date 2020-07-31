<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Product;

use Magento\CatalogStaging\Model\Product\Hydrator;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HydratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /**
     * @var \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $initializationHelper;

    /** @var \Magento\Catalog\Controller\Adminhtml\Product\Builder|\PHPUnit_Framework_MockObject_MockObject */
    protected $productBuilder;

    /** @var \Magento\Catalog\Model\Product\TypeTransitionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $productTypeManager;

    /** @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $versionManager;

    /** @var \Magento\Staging\Api\UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $updateRepository;

    /** @var \Magento\Catalog\Api\CategoryLinkManagementInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryLinkManagement;

    /** @var \Magento\Staging\Model\Entity\PersisterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityPersister;

    /** @var \Magento\CatalogStaging\Model\Product\Retriever|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRetriever;

    /** @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject */
    protected $product;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Staging\Api\Data\UpdateInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $update;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var Hydrator */
    protected $hydrator;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->initializationHelper = $this->getMockBuilder(
            \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->productBuilder = $this->getMockBuilder(\Magento\Catalog\Controller\Adminhtml\Product\Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeManager = $this->getMockBuilder(\Magento\Catalog\Model\Product\TypeTransitionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManager = $this->getMockBuilder(\Magento\Staging\Model\VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->categoryLinkManagement = $this->getMockBuilder(
            \Magento\Catalog\Api\CategoryLinkManagementInterface::class
        )->getMockForAbstractClass();
        $this->entityPersister = $this->getMockBuilder(\Magento\Staging\Model\Entity\PersisterInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(\Magento\CatalogStaging\Model\Product\Retriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getIdFieldName',
                'setNewsFromDate',
                'setNewsToDate',
                'getId',
                'getSku',
                'getCategoryIds',
                'getEntityId',
                'setStoreId'
            ])
            ->getMock();
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->update = $this->getMockBuilder(\Magento\Staging\Api\Data\UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMockForAbstractClass();

        $this->hydrator = new Hydrator(
            $this->context,
            $this->initializationHelper,
            $this->productBuilder,
            $this->productTypeManager,
            $this->versionManager,
            $this->updateRepository,
            $this->entityRetriever,
            $this->storeManager
        );
    }

    public function testHydrate()
    {
        $versionId = 1;
        $startTime = '12/12/2016 14:34:12';
        $endTime = '12/12/2017 14:34:12';
        $storeId = 27;

        $data = [
            'product' => [
                'is_new' => true,
                'copy_to_stores' => [
                    34 => [
                        [
                            'copy_to' => $storeId,
                        ]
                    ],
                ],
                'website_ids' => [
                    34 => true
                ],
            ],
        ];

        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->productBuilder->expects($this->once())
            ->method('build')
            ->with($this->request)
            ->willReturn($this->product);
        $this->initializationHelper->expects($this->once())
            ->method('initialize')
            ->with($this->product)
            ->willReturnArgument(0);
        $this->productTypeManager->expects($this->once())
            ->method('processProduct')
            ->with($this->product);
        $this->versionManager->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn($this->update);
        $this->update->expects($this->once())
            ->method('getId')
            ->willReturn($versionId);
        $this->updateRepository->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($this->update);
        $this->update->expects($this->once())
            ->method('getStartTime')
            ->willReturn($startTime);
        $this->update->expects($this->once())
            ->method('getEndTime')
            ->willReturn($endTime);
        $this->product->expects($this->once())
            ->method('setNewsFromDate')
            ->with($startTime);
        $this->product->expects($this->once())
            ->method('setNewsToDate')
            ->with($endTime);
        $this->product->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->assertEquals($this->product, $this->hydrator->hydrate($data));
    }
}
