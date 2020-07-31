<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * GiftRegistry item instance
     *
     * @var \Magento\GiftRegistry\Model\Item
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogUrlMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    protected function setUp()
    {
        $contextMock = $this->createMock(\Magento\Framework\Model\Context::class);
        $registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $productRepositoryMock = $this->createMock(\Magento\Catalog\Model\ProductRepository::class);
        $itemOptionMock = $this->createPartialMock(\Magento\GiftRegistry\Model\Item\OptionFactory::class, ['create']);
        $this->catalogUrlMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Url::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->cartMock = $this->createMock(\Magento\Checkout\Model\Cart::class);

        $resourceMock = $this->createPartialMock(
            \Magento\Framework\Model\ResourceModel\AbstractResource::class,
            ['_construct', 'getConnection', 'getIdFieldName']
        );

        $this->productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                'getStatus',
                'getName',
                'getId',
                'isVisibleInSiteVisibility',
                'addCustomOption',
                'setUrlDataObject',
                'getVisibleInSiteVisibilities',
                'getUrlDataObject',
                'getStoreId',
                'isSalable',
                '__wakeup',
                '__sleep'
            ]
        );

        $this->serializerMock = $this->createMock(Json::class);

        $this->model = new \Magento\GiftRegistry\Model\Item(
            $contextMock,
            $registryMock,
            $productRepositoryMock,
            $itemOptionMock,
            $this->catalogUrlMock,
            $this->messageManagerMock,
            $resourceMock,
            null,
            [],
            $this->serializerMock
        );
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::__construct
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductDisabled()
    {
        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED));
        $this->productMock->expects($this->never())->method('isVisibleInSiteVisibility');

        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->model->setData('product', $this->productMock);
        $this->assertEquals(false, $this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartRequestedQuantityExceeded()
    {
        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED));

        $this->productMock->expects($this->once())->method('getName')->will($this->returnValue('Product'));
        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', 1);
        $this->model->setData('qty_fullfilled', 0);

        $this->messageManagerMock->expects($this->once())
            ->method('addNotice')
            ->with('The quantity of "Product" product added to cart exceeds the quantity desired by '
                . 'the Gift Registry owner. The quantity added has been adjusted to meet remaining quantity 1.');

        $this->assertEquals(false, $this->model->addToCart($this->cartMock, 5));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartThatAlreadyContainsQuantityThatExceedsRequested()
    {
        $registryItemId = 17;
        $productId = 1;
        $itemProductQty = 1;
        $registryQty = 1;
        $fullFilledQty = 0;
        $requestedQty = 5;

        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $itemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\AbstractItem::class)
            ->setMethods(['getGiftregistryItemId', 'getProduct', 'getQty'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED));
        $this->productMock->expects($this->any())->method('getName')->will($this->returnValue('Product'));
        $this->productMock->expects($this->any())->method('getId')->will($this->returnValue($productId));

        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([$productId]));
        $this->cartMock->expects($this->any())->method('getQuote')->will($this->returnValue($quoteMock));

        $itemMock->expects($this->any())->method('getProduct')->will($this->returnValue($this->productMock));
        $itemMock->expects($this->any())->method('getGiftregistryItemId')->will($this->returnValue($registryItemId));
        $itemMock->expects($this->any())->method('getQty')->will($this->returnValue($itemProductQty));

        $quoteMock->expects($this->once())->method('getAllItems')->will($this->returnValue([$itemMock]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', $registryQty);
        $this->model->setData('qty_fullfilled', $fullFilledQty);
        $this->model->setData('id', $registryItemId);

        $this->messageManagerMock->expects($this->exactly(2))->method('addNotice');
        $this->assertEquals(false, $this->model->addToCart($this->cartMock, $requestedQty));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductInvisibleForCurrentStore()
    {
        $storeId = 3;

        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->will($this->returnValue(false));
        $this->productMock->expects($this->any())->method('getStoreId')->will($this->returnValue($storeId));
        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $storeId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertEquals(false, $this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductFromOtherStoreWithoutUrlRewrites()
    {
        $productStoreId = 3;
        $registryStoreId = 2;
        $productId = 1;

        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->will($this->returnValue(false));
        $this->productMock->expects($this->any())->method('getStoreId')->will($this->returnValue($productStoreId));
        $this->productMock->expects($this->any())->method('getId')->will($this->returnValue($productId));
        $this->productMock->expects($this->never())->method('setUrlDataObject');

        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->catalogUrlMock->expects($this->once())->method('getRewriteByProductStore')
            ->with([$productId => $registryStoreId])->will($this->returnValue([]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $registryStoreId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertEquals(false, $this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductUrlIsNotVisibleInSite()
    {
        $productStoreId = 3;
        $registryStoreId = 2;
        $productId = 1;

        $objectMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getVisibility']);
        $objectMock->expects($this->once())->method('getVisibility')->will($this->returnValue(1));

        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->will($this->returnValue(false));
        $this->productMock->expects($this->any())->method('getStoreId')->will($this->returnValue($productStoreId));
        $this->productMock->expects($this->any())->method('getId')->will($this->returnValue($productId));
        $this->productMock->expects($this->once())->method('setUrlDataObject');
        $this->productMock->expects($this->once())->method('getUrlDataObject')->will($this->returnValue($objectMock));
        $this->productMock->expects($this->once())->method('getVisibleInSiteVisibilities')
            ->will($this->returnValue([]));
        $this->productMock->expects($this->never())->method('isSalable');

        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->catalogUrlMock->expects($this->once())->method('getRewriteByProductStore')
            ->with([$productId => $registryStoreId])->will($this->returnValue([$productId => $productId]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('store_id', $registryStoreId);
        $this->model->setData('qty', 1);

        $this->messageManagerMock->expects($this->never())->method('addNotice');
        $this->assertEquals(false, $this->model->addToCart($this->cartMock, 1));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage This product(s) is out of stock.
     *
     * @covers \Magento\GiftRegistry\Model\Item::addToCart
     */
    public function testAddToCartProductNotSalable()
    {
        $this->productMock->expects($this->once())->method('getStatus')
            ->will($this->returnValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        $this->productMock->expects($this->once())->method('isVisibleInSiteVisibility')
            ->will($this->returnValue(true));
        $this->productMock->expects($this->any())->method('isSalable')->will($this->returnValue(false));

        $this->cartMock->expects($this->once())->method('getProductIds')->will($this->returnValue([]));

        $this->model->setData('product', $this->productMock);
        $this->model->setData('qty', 1);

        $this->model->addToCart($this->cartMock, 1);
    }
}
