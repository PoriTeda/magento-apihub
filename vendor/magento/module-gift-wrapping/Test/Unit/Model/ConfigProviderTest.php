<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\GiftWrapping\Model\ConfigProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftWrapping\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Session;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Quote\Model\Quote\Address|\PHPUnit_Framework_MockObject_MockObject */
    protected $address;
    
    /** @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject */
    protected $quote;
    
    /** @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutSession;
    
    /** @var \Magento\Checkout\Model\CartFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutCartFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $productRepository;

    /** @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject */
    protected $product;

    /** @var \Magento\GiftWrapping\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $giftWrappingData;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     * |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $wrappingCollectionFactory;

    /** @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $wrappingCollection;

    /** @var \Magento\GiftWrapping\Model\Wrapping|\PHPUnit_Framework_MockObject_MockObject */
    protected $wrappingItem;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \Magento\Framework\Pricing\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $pricingHelper;

    /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $urlBuilder;

    /** @var \Magento\Framework\View\Asset\Repository|\PHPUnit_Framework_MockObject_MockObject */
    protected $assetRepo;

    /** @var  ConfigProvider|\PHPUnit_Framework_MockObject_MockObject*/
    protected $provider;

    /** @var  \Magento\Quote\Model\QuoteIdMaskFactory|\PHPUnit_Framework_MockObject_MockObject*/
    protected $quoteIdMaskFactory;

    /** @var \Magento\Quote\Model\QuoteIdMask|\PHPUnit_Framework_MockObject_MockObject */
    protected $quoteIdMask;

    /** @var  \Magento\Quote\Model\Cart\Totals|\PHPUnit_Framework_MockObject_MockObject  */
    protected $totalsMock;

    /** @var TaxClassKeyInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject  */
    protected $taxClassKeyFactory;

    /** @var TaxClassKeyInterface|\PHPUnit_Framework_MockObject_MockObject  */
    protected $taxClassKey;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $this->totalsMock = $this->createPartialMock(
            \Magento\Quote\Model\Cart\Totals::class,
            ['getGwCardPrice', '__wakeUp']
        );
        $this->quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getAllShippingAddresses',
                'getIsMultiShipping',
                'getBillingAddress',
                'hasGwId',
                'getGwId',
                'getTotals',
                'getShippingAddress',
                'getAllItems'
            ]
        );
        $this->checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->checkoutCartFactory = $this->createPartialMock(\Magento\Checkout\Model\CartFactory::class, ['create']);
        $this->giftWrappingData = $this->createMock(\Magento\GiftWrapping\Helper\Data::class);
        $this->urlBuilder = $this->getMockForAbstractClass(\Magento\Framework\UrlInterface::class, [], '', false);
        $this->assetRepo = $this->createMock(\Magento\Framework\View\Asset\Repository::class);

        $this->request = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false
        );
        $this->logger = $this->getMockForAbstractClass(\Psr\Log\LoggerInterface::class, [], '', false);
        $this->pricingHelper = $this->createMock(\Magento\Framework\Pricing\Helper\Data::class);
        $this->productRepository = $this->getMockForAbstractClass(
            \Magento\Catalog\Api\ProductRepositoryInterface::class,
            [],
            '',
            false
        );
        $this->product = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getGiftWrappingAvailable']);
        $this->storeManager = $this->getMockForAbstractClass(
            \Magento\Store\Model\StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->wrappingCollectionFactory = $this->createPartialMock(
            \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory::class,
            ['create']
        );
        $this->wrappingCollection = $this->createMock(
            \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection::class
        );
        $this->wrappingItem = $this->createPartialMock(
            \Magento\GiftWrapping\Model\Wrapping::class,
            ['getBasePrice', 'setTaxClassKey', 'getImageUrl', 'getId']
        );
        $this->quoteIdMaskFactory = $this->createPartialMock(
            \Magento\Quote\Model\QuoteIdMaskFactory::class,
            ['create']
        );
        $this->quoteIdMask = $this->createPartialMock(\Magento\Quote\Model\QuoteIdMask::class, ['load', 'getMaskedId']);
        $this->taxClassKeyFactory = $this->createPartialMock(
            \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory::class,
            ['create']
        );
        $this->taxClassKey = $this->getMockForAbstractClass(
            \Magento\Tax\Api\Data\TaxClassKeyInterface::class,
            [],
            '',
            false
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetConfig()
    {
        $this->address->expects($this->any())->method('getId')->willReturn(2);
        $this->quote->expects($this->atLeastOnce())->method('getAllShippingAddresses')->willReturn([$this->address]);
        $this->quote->expects($this->any())->method('getIsMultiShipping')->willReturn(true);
        $this->quote->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn($this->address);
        $this->quote->expects($this->atLeastOnce())->method('hasGwId')->willReturn(true);
        $this->quote->expects($this->atLeastOnce())->method('getGwId')->willReturn(3);
        $shippingAddressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $methods = ['getProduct', 'getParentItem', 'getId', 'setTaxClassKey'];
        $quoteItemMock = $this->createPartialMock(\Magento\Quote\Model\ResourceModel\Quote\Item::class, $methods);
        $prodMethods = ['getGiftWrappingAvailable', 'getGiftWrappingPrice', ];
        $productMock = $this->createPartialMock(\Magento\Catalog\Model\Product::class, $prodMethods);
        $this->checkoutSession->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $this->quote->expects($this->once())->method('getAllItems')->willReturn([$quoteItemMock]);
        $quoteItemMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $checkoutCart = $this->createMock(\Magento\Checkout\Model\Cart::class);
        $this->checkoutCartFactory->expects($this->atLeastOnce())->method('create')->willReturn($checkoutCart);

        $item = $this->createPartialMock(
            \Magento\Quote\Model\ResourceModel\Quote\Item::class,
            ['getProductId', 'hasGwId', 'getGwId', 'getId', 'getProduct']
        );
        $item->expects($this->never())->method('getProductId');
        $item->expects($this->once())->method('hasGwId')->willReturn(true);
        $item->expects($this->once())->method('getGwId')->willReturn(13);
        $item->expects($this->once())->method('getId')->willReturn(2);

        $checkoutCart->expects($this->atLeastOnce())->method('getItems')->willReturn([$item]);

        $this->product->expects($this->once())->method('getGiftWrappingAvailable')->willReturn(true);
        $this->productRepository->expects($this->never())->method('getById');
        $item->expects($this->once())->method('getProduct')->willReturn($this->product);

        $this->wrappingItem->expects($this->once())->method('getBasePrice')->willReturn('13');
        $this->wrappingItem->expects($this->atLeastOnce())->method('setTaxClassKey');
        $this->wrappingItem->expects($this->once())->method('getImageUrl')->willReturn('http://image-url.com');
        $this->wrappingItem->expects($this->any())->method('getId')->willReturn(83);

        $this->wrappingCollection->expects($this->once())->method('addStoreAttributesToResult')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('applyStatusFilter')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('applyWebsiteFilter')->willReturnSelf();
        $this->wrappingCollection->expects($this->once())->method('getItems')->willReturn([$this->wrappingItem]);
        $this->wrappingCollectionFactory->expects($this->once())->method('create')
            ->willReturn($this->wrappingCollection);

        $this->request->expects($this->once())->method('isSecure')->willReturn(true);
        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())->method('getId')->willReturn(11);
        $store->expects($this->once())->method('getWebsiteId')->willReturn(21);

        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);

        $this->giftWrappingData->expects($this->atLeastOnce())->method('getPrice')->willReturn(73);
        $this->giftWrappingData->expects($this->any())->method('getPrintedCardPrice')->willReturn(23);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('getWrappingTaxClass')->willReturn('tax-class');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('isGiftWrappingAvailableForOrder');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('isGiftWrappingAvailableForItems');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowPrintedCard')->willReturn(true);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowGiftReceipt');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('allowGiftReceipt');
        $this->giftWrappingData->expects($this->atLeastOnce())->method('displayCartWrappingBothPrices')
            ->willReturn(false);
        $this->giftWrappingData->expects($this->atLeastOnce())->method('displayCartWrappingIncludeTaxPrice')
            ->willReturn(false);

        $this->quoteIdMask->expects($this->once())->method('load')->willReturnSelf();
        $this->quoteIdMask->expects($this->once())->method('getMaskedId')->willReturn('masked-id');

        $this->quoteIdMaskFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->quoteIdMask);

        $this->taxClassKey->expects($this->atLeastOnce())
            ->method('setType')
            ->with(TaxClassKeyInterface::TYPE_ID)
            ->willReturnSelf();
        $this->taxClassKey->expects($this->atLeastOnce())
            ->method('setValue')
            ->with('tax-class')
            ->willReturnSelf();
        $this->taxClassKeyFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->taxClassKey);
        $this->pricingHelper->expects($this->atLeastOnce())
            ->method('currency');

        $this->provider = new ConfigProvider(
            $this->checkoutCartFactory,
            $this->productRepository,
            $this->giftWrappingData,
            $this->storeManager,
            $this->wrappingCollectionFactory,
            $this->urlBuilder,
            $this->assetRepo,
            $this->request,
            $this->logger,
            $this->checkoutSession,
            $this->pricingHelper,
            $this->quoteIdMaskFactory,
            $this->taxClassKeyFactory
        );

        $this->provider->getConfig();
    }
}
