<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Helper;

use Magento\GiftWrapping\Model\System\Config\Source\Display\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteDetailsItemFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteDetailsFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxCalculationService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceCurrency;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $subject;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $className = \Magento\GiftWrapping\Helper\Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->storeManager = $arguments['storeManager'];
        $this->quoteDetailsItemFactory =
            $this->getMockBuilder(\Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMockForAbstractClass();
        $this->quoteDetailsFactory = $this->getMockBuilder(\Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->taxCalculationService = $arguments['taxCalculationService'];
        $this->priceCurrency = $arguments['priceCurrency'];

        $arguments['quoteDetailsItemFactory'] = $this->quoteDetailsItemFactory;
        $arguments['quoteDetailsFactory'] = $this->quoteDetailsFactory;

        $this->subject = $objectManager->getObject($className, $arguments);
    }

    /**
     * @param bool $useBillingAddress
     * @dataProvider getPriceDataProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPrice($useBillingAddress)
    {
        $customerId = 5494;
        $storeId = 2;
        $taxClassKeyValue = 13;
        $item = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getTaxClassKey']);
        $price = 12.45;
        $includeTax = true;
        $shippingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $billingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $shippingDataModel = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\AddressInterface::class,
            [],
            'shippingDataModel',
            false
        );

        $billingDataModel = null;
        if ($useBillingAddress) {
            $billingDataModel = $this->getMockForAbstractClass(
                \Magento\Customer\Api\Data\AddressInterface::class,
                [],
                'billingDataMode',
                false
            );
            $billingDataModel->expects($this->once())
                ->method('getCustomerId')
                ->willReturn($customerId);
        } else {
            $shippingDataModel->expects($this->once())
                ->method('getCustomerId')
                ->willReturn($customerId);
        }

        $shippingAddress->expects($this->once())
            ->method('getDataModel')
            ->willReturn($shippingDataModel);
        $billingAddress->expects($this->once())
            ->method('getDataModel')
            ->willReturn($billingDataModel);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeId);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $taxClassKey = $this->getMockForAbstractClass(\Magento\Tax\Api\Data\TaxClassKeyInterface::class, [], '', false);
        $taxClassKey->expects($this->once())
            ->method('getValue')
            ->willReturn($taxClassKeyValue);
        $item->expects($this->once())
            ->method('getTaxClassKey')
            ->willReturn($taxClassKey);

        $quoteDetailsItem = $this->getMockForAbstractClass(
            \Magento\Tax\Api\Data\QuoteDetailsItemInterface::class,
            [],
            '',
            false
        );
        $quoteDetailsItem->expects($this->once())
            ->method('setQuantity')
            ->with(1)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setCode')
            ->with('giftwrapping_code')
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setTaxClassId')
            ->with($taxClassKeyValue)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setIsTaxIncluded')
            ->with(false)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setType')
            ->with('giftwrapping_type')
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setTaxClassKey')
            ->with($taxClassKey)
            ->willReturnSelf();
        $quoteDetailsItem->expects($this->once())
            ->method('setUnitPrice')
            ->with($price)
            ->willReturnSelf();

        $this->quoteDetailsItemFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($quoteDetailsItem);
        $quoteDetails = $this->getMockForAbstractClass(
            \Magento\Tax\Api\Data\QuoteDetailsInterface::class,
            [],
            '',
            false
        );
        $quoteDetails->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingDataModel)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setBillingAddress')
            ->with($billingDataModel)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setCustomerTaxClassId')
            ->with(null)
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setItems')
            ->with([$quoteDetailsItem])
            ->willReturnSelf();
        $quoteDetails->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->willReturnSelf();
        $this->quoteDetailsFactory->expects($this->once())
            ->method('create')
            ->willReturn($quoteDetails);

        $taxDetailItem = $this->getMockForAbstractClass(
            \Magento\Tax\Api\Data\TaxDetailsItemInterface::class,
            [],
            '',
            false
        );
        $taxDetailItem->expects($this->once())
            ->method('getPriceInclTax')
            ->willReturn($price);
        $taxDetail = $this->getMockForAbstractClass(\Magento\Tax\Api\Data\TaxDetailsInterface::class, [], '', false);
        $taxDetail->expects($this->once())
            ->method('getItems')
            ->willReturn([$taxDetailItem]);
        $this->taxCalculationService->expects($this->once())
            ->method('calculateTax')
            ->with($quoteDetails, $storeId, true)
            ->willReturn($taxDetail);

        $this->subject->getPrice($item, $price, $includeTax, $shippingAddress, $billingAddress);
    }

    /**
     * @return array
     */
    public function getPriceDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testGetPriceWithoutTaxCalculation()
    {
        $item = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getTaxClassKey']);
        $price = 12;
        $includeTax = false;
        $shippingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $billingAddress = $this->createMock(\Magento\Quote\Model\Quote\Address::class);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        $taxClassKey = $this->getMockForAbstractClass(\Magento\Tax\Api\Data\TaxClassKeyInterface::class, [], '', false);
        $item->expects($this->once())
            ->method('getTaxClassKey')
            ->willReturn($taxClassKey);

        $this->priceCurrency
            ->expects($this->once())
            ->method('round')
            ->with($price)
            ->willReturn($price);

        $this->subject->getPrice($item, $price, $includeTax, $shippingAddress, $billingAddress);
    }

    public function testIsGiftWrappingAvailableIfProductConfigIsNull()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOWED_FOR_ITEMS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForProduct(null, $storeMock));
    }

    /**
     * @param int $expectedResult
     * @param int $configValue
     * @param mixed $productValue
     * @dataProvider productConfigDataProvider
     */
    public function testIsGiftWrappingAvailableForProduct($expectedResult, $configValue, $productValue)
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOWED_FOR_ITEMS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($configValue));

        $this->assertEquals(
            $expectedResult,
            $this->subject->isGiftWrappingAvailableForProduct($productValue, $storeMock)
        );
    }

    /**
     * @return array
     */
    public function productConfigDataProvider()
    {
        return [
            [1 , 1, ''],
            [1 , 1, null],
            [1 , 1, Boolean::VALUE_USE_CONFIG],
            [0 , 1, 0],
            [1, 0, 1],
        ];
    }

    public function testIsGiftWrappingAvailableForItems()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOWED_FOR_ITEMS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForItems($storeMock));
    }

    public function testIsGiftWrappingAvailableForOrder()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOWED_FOR_ORDER,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->isGiftWrappingAvailableForOrder($storeMock));
    }

    public function testGetWrappingTaxClass()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_TAX_CLASS,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->getWrappingTaxClass($storeMock));
    }

    public function testAllowPrintedCard()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOW_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->allowPrintedCard($storeMock));
    }

    public function testAllowGiftReceipt()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_ALLOW_GIFT_RECEIPT,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->allowGiftReceipt($storeMock));
    }

    public function testGetPrintedCardPrice()
    {
        $scopeConfig = 'scope_config';
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRINTED_CARD_PRICE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue($scopeConfig));
        $this->assertEquals($scopeConfig, $this->subject->getPrintedCardPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertTrue($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingIncludeTaxPriceWhenDisplayTypeIsExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displayCartWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingExcludeTaxPriceWhenDisplayTypeIsIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertFalse($this->subject->displayCartWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingExcludeTaxPriceWhenDisplayTypeIsExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertTrue($this->subject->displayCartWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplayCartWrappingBothPricesIsIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displayCartWrappingBothPrices($storeMock));
    }

    public function testDisplayCartWrappingBothPricesIsBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displayCartWrappingBothPrices($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsExcludingTaxPrice()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardIncludeTaxPriceIsIncludingTaxPrice()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertTrue($this->subject->displayCartCardIncludeTaxPrice($storeMock));
    }

    public function testDisplayCartCardBothPricesIncludingTaxPrice()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertFalse($this->subject->displayCartCardBothPrices($storeMock));
    }

    public function testDisplayCartCardBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_CART_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displayCartCardBothPrices($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertTrue($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingIncludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displaySalesWrappingIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingExcludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertTrue($this->subject->displaySalesWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingExcludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertFalse($this->subject->displaySalesWrappingExcludeTaxPrice($storeMock));
    }

    public function testDisplaySalesWrappingBothPricesDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertFalse($this->subject->displaySalesWrappingBothPrices($storeMock));
    }

    public function testDisplaySalesWrappingBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_WRAPPING,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displaySalesWrappingBothPrices($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeIncludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_INCLUDING_TAX));
        $this->assertTrue($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardIncludeTaxPriceDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displaySalesCardIncludeTaxPrice($storeMock));
    }

    public function testDisplaySalesCardBothPricesDisplayTypeExcludingTax()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_EXCLUDING_TAX));
        $this->assertFalse($this->subject->displaySalesCardBothPrices($storeMock));
    }

    public function testDisplaySalesCardBothPricesDisplayTypeBoth()
    {
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                \Magento\GiftWrapping\Helper\Data::XML_PATH_PRICE_DISPLAY_SALES_PRINTED_CARD,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeMock
            )
            ->will($this->returnValue(Type::DISPLAY_TYPE_BOTH));
        $this->assertTrue($this->subject->displaySalesCardBothPrices($storeMock));
    }
}
