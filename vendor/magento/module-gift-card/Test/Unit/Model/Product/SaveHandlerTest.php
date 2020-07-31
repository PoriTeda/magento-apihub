<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Model\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Model\Product\SaveHandler
     */
    private $saveHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPool;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $getAmountIdsByProduct;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->metadataPool = $this->createMock(\Magento\Framework\EntityManager\MetadataPool::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->getAmountIdsByProduct = $this->createMock(
            \Magento\GiftCard\Model\ResourceModel\Db\GetAmountIdsByProduct::class
        );
        $this->saveHandler = $objectManager->getObject(
            \Magento\GiftCard\Model\Product\SaveHandler::class,
            [
                'metadataPool' => $this->metadataPool,
                'storeManager' => $this->storeManager,
                'getAmountIdsByProduct' => $this->getAmountIdsByProduct
            ]
        );
    }

    public function testExecute()
    {
        $giftCardAmounts = ['test' => []];
        $entityData = ['row_id' => 1];
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $metadataMock = $this->createMock(\Magento\Framework\EntityManager\EntityMetadataInterface::class);
        $hydratorMock = $this->createMock(\Magento\Framework\EntityManager\HydratorInterface::class);
        $productMock->expects($this->once())->method('getTypeId')->willReturn(Giftcard::TYPE_GIFTCARD);
        $extensionAttributes = $this->createPartialMock(
            \Magento\Catalog\Api\Data\ProductExtension::class,
            ['getGiftcardAmounts']
        );
        $this->metadataPool->expects($this->once())->method('getMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getLinkField')->willReturn('row_id');
        $this->metadataPool
            ->expects($this->once())
            ->method('getHydrator')
            ->with(ProductInterface::class)
            ->willReturn($hydratorMock);
        $hydratorMock->expects($this->once())->method('extract')->with($productMock)->willReturn($entityData);
        $productMock->expects($this->once())->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())->method('getGiftcardAmounts')->willReturn([]);
        $productMock->expects($this->once())->method('getData')->with('giftcard_amounts')->willReturn($giftCardAmounts);
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->getAmountIdsByProduct->expects($this->once())->method('execute')->with('row_id', 1, 1)->willReturn([]);
        $this->saveHandler->execute($productMock);
    }
}
