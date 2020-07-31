<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Model\Product;

/**
 * UpdateScheduler unit test.
 */
class UpdateSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateService;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManager;

    /**
     * @var \Magento\CatalogStaging\Api\ProductStagingInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productStaging;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepository;

    /**
     * @var \Magento\CatalogStaging\Model\Product\UpdateScheduler
     */
    private $updateScheduler;

    /**
     * Set up.
     */
    protected function setUp()
    {
        $this->updateService = $this->getMockBuilder(
            \Magento\Staging\Controller\Adminhtml\Entity\Update\Service::class
        )->disableOriginalConstructor()->getMock();
        $this->versionManager = $this->getMockBuilder(
            \Magento\Staging\Model\VersionManager::class
        )->disableOriginalConstructor()->getMock();
        $this->productStaging = $this->getMockBuilder(
            \Magento\CatalogStaging\Api\ProductStagingInterface::class
        )->disableOriginalConstructor()->getMock();
        $this->productRepository = $this->getMockBuilder(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        )->disableOriginalConstructor()->getMock();
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->updateScheduler = $objectManager->getObject(
            \Magento\CatalogStaging\Model\Product\UpdateScheduler::class,
            [
                'updateService' => $this->updateService,
                'versionManager' => $this->versionManager,
                'productStaging' => $this->productStaging,
                'productRepository' => $this->productRepository,
            ]
        );
    }

    /**
     * Test schedule method.
     */
    public function testSchedule()
    {
        $update = $this->getMockBuilder(
            \Magento\Staging\Api\Data\UpdateInterface::class
        )->disableOriginalConstructor()->getMock();
        $this->updateService->expects($this->exactly(1))->method('createUpdate')->willReturn($update);
        $this->versionManager->expects($this->exactly(1))->method('setCurrentVersionId');
        $product = $this->getMockBuilder(
            \Magento\Catalog\Api\Data\ProductInterface::class
        )->disableOriginalConstructor()->getMock();
        $this->productRepository->expects($this->exactly(1))->method('get')->willReturn($product);
        $this->productStaging->expects($this->exactly(1))->method('schedule');
        $this->assertTrue($this->updateScheduler->schedule('sku', [], 0));
    }
}
