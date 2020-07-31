<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Helper\GiftRegistry;

use Magento\GiftCard\Helper\GiftRegistry\Plugin as GiftRegistryHelperPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftRegistry\Helper\Data as DataHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Catalog\Model\Product;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GiftRegistryHelperPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var DataHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var QuoteItem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteItemMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var ProductType|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productTypeMock;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->subjectMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductType', 'getProductId', 'getTypeId'])
            ->getMock();
        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productTypeMock = $this->getMockBuilder(ProductType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            GiftRegistryHelperPlugin::class,
            ['productRepository' => $this->productRepositoryMock]
        );
    }

    public function testAfterCanAddToGiftRegistryPhysicalCard()
    {
        $productId = 333222555;

        $this->quoteItemMock->expects(static::once())
            ->method('getProductType')
            ->willReturn(ProductType::TYPE_GIFTCARD);
        $this->quoteItemMock->expects(static::once())
            ->method('getProductId')
            ->willReturn($productId);
        $this->productRepositoryMock->expects(static::once())
            ->method('getById')
            ->with($productId)
            ->willReturn($this->productMock);
        $this->productMock->expects(static::once())
            ->method('getTypeInstance')
            ->willReturn($this->productTypeMock);
        $this->productTypeMock->expects(static::once())
            ->method('isTypePhysical')
            ->willReturn(true);

        $this->assertEquals(
            true,
            $this->plugin->afterCanAddToGiftRegistry($this->subjectMock, true, $this->quoteItemMock)
        );
    }

    public function testAfterCanAddToGiftRegistryVirtualCard()
    {
        $this->quoteItemMock->expects(static::never())
            ->method('getProductType');
        $this->quoteItemMock->expects(static::never())
            ->method('getTypeId');

        $this->assertEquals(
            false,
            $this->plugin->afterCanAddToGiftRegistry($this->subjectMock, false, $this->quoteItemMock)
        );
    }
}
