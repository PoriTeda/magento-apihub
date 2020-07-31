<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Model\Catalog\Product\Type;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftcardTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_customOptions;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResource;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Option
     */
    protected $_optionResource;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_store;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerMock;

    /**
     * @var \Magento\Quote\Model\Quote\Item\Option
     */
    protected $_quoteItemOption;

    /**
     * Serializer interface instance.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->_store = $this->createPartialMock(
            \Magento\Store\Model\Store::class,
            ['getCurrentCurrencyRate', '__sleep', '__wakeup']
        );
        $this->_storeManagerMock = $this->getMockBuilder(
            \Magento\Store\Model\StoreManagerInterface::class
        )->disableOriginalConstructor()->setMethods(
            ['getStore']
        )->getMockForAbstractClass();
        $this->_storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($this->_store));
        $this->_mockModel(['_isStrictProcessMode']);
    }

    /**
     * Create model Mock
     *
     * @param $mockedMethods
     */
    protected function _mockModel($mockedMethods)
    {
        $eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $productRepository = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $filesystem =
            $this->getMockBuilder(\Magento\Framework\Filesystem::class)->disableOriginalConstructor()->getMock();
        $storage = $this->getMockBuilder(
            \Magento\MediaStorage\Helper\File\Storage\Database::class
        )->disableOriginalConstructor()->getMock();
        $locale = $this->createPartialMock(\Magento\Framework\Locale\Format::class, ['getNumber']);
        $locale->expects($this->any())->method('getNumber')->will($this->returnArgument(0));
        $coreRegistry = $this->createMock(\Magento\Framework\Registry::class);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $productOption = $this->createMock(\Magento\Catalog\Model\Product\Option::class);
        $eavConfigMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $productTypeMock = $this->createMock(\Magento\Catalog\Model\Product\Type::class);
        $priceCurrency = $this->getMockBuilder(\Magento\Framework\Pricing\PriceCurrencyInterface::class)->getMock();
        $priceCurrency->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($price) {
                        return round($price, 2);
                    }
                )
            );
        $this->serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['unserialize'])
            ->getMockForAbstractClass();
        $this->_model = $this->getMockBuilder(\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::class)
            ->setMethods($mockedMethods)
            ->setConstructorArgs(
                [
                    $productOption,
                    $eavConfigMock,
                    $productTypeMock,
                    $eventManager,
                    $storage,
                    $filesystem,
                    $coreRegistry,
                    $logger,
                    $productRepository,
                    $this->_storeManagerMock,
                    $locale,
                    $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class),
                    $priceCurrency,
                    $this->serializer
                ]
            )
            ->getMock();
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _preConditions()
    {
        $this->_store->expects($this->any())->method('getCurrentCurrencyRate')->will($this->returnValue(1));
        $this->_productResource = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product::class);
        $this->_optionResource = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Option::class);

        $productCollection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);

        $itemFactoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory::class,
            ['create']
        );
        $stockItemFactoryMock = $this->createPartialMock(
            \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory::class,
            ['create']
        );
        $productFactoryMock = $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']);
        $categoryFactoryMock = $this->createPartialMock(\Magento\Catalog\Model\CategoryFactory::class, ['create']);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = $objectManagerHelper->getConstructArguments(
            \Magento\Catalog\Model\Product::class,
            [
                'itemOptionFactory' => $itemFactoryMock,
                'stockItemFactory' => $stockItemFactoryMock,
                'productFactory' => $productFactoryMock,
                'categoryFactory' => $categoryFactoryMock,
                'resource' => $this->_productResource,
                'resourceCollection' => $productCollection,
                'collectionFactory' => $this->createMock(\Magento\Framework\Data\CollectionFactory::class)
            ]
        );
        $this->_product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->setMethods(
                [
                    'getGiftcardAmounts',
                    'getAllowOpenAmount',
                    'getOpenAmountMax',
                    'getOpenAmountMin',
                    '__wakeup',
                    'getSkipCheckRequiredOption'
                ]
            )
            ->setConstructorArgs($arguments)
            ->getMock();
        $this->_product->expects($this->any())
            ->method('getSkipCheckRequiredOption')
            ->willReturn(true);

        $this->_customOptions = [];
        for ($i = 1; $i <= 3; $i++) {
            $option = $objectManagerHelper->getObject(
                \Magento\Catalog\Model\Product\Option::class,
                ['resource' => $this->_optionResource]
            );
            $option->setIdFieldName('id');
            $option->setId($i);
            $option->setIsRequire(true);
            $this->_customOptions[\Magento\Catalog\Model\Product\Type\AbstractType::OPTION_PREFIX . $i] =
                new \Magento\Framework\DataObject(['value' => 'value']);
            $this->_product->addOption($option);
        }

        $this->_quoteItemOption = $this->createMock(\Magento\Quote\Model\Quote\Item\Option::class);

        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;

        $this->_product->expects($this->any())->method('getAllowOpenAmount')->will($this->returnValue(true));

        $this->_product->setSkipCheckRequiredOption(false);
        $this->_product->setCustomOptions($this->_customOptions);
    }

    public function testValidateEmptyFields()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue("{}")
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));

        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage('Please specify all the required information.');
        $this->_model->checkProductBuyState($this->_product);
    }

    public function testValidateEmptyAmount()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email"}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));

        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a gift card amount.');
    }

    public function testValidateMaxAmount()
    {
        $this->_preConditions();
        $this->_product->expects($this->once())->method('getOpenAmountMax')->will($this->returnValue(10));
        $this->_product->expects($this->once())->method('getOpenAmountMin')->will($this->returnValue(3));
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_giftcard_amount":15}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Gift Card max amount is ');
    }

    public function testValidateMinAmount()
    {
        $this->_preConditions();
        $this->_product->expects($this->once())->method('getOpenAmountMax')->will($this->returnValue(10));
        $this->_product->expects($this->once())->method('getOpenAmountMin')->will($this->returnValue(3));
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_giftcard_amount":2}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Gift Card min amount is ');
    }

    public function testValidateNoAllowedAmount()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"custom_amount":7}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a gift card amount.');
    }

    public function testValidateRecipientName()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a recipient name.');
    }

    public function testValidateSenderName()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a sender name.');
    }

    public function testValidateRecipientEmail()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_sender_email":"email",'
                . '"giftcard_amount":5}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a recipient email.');
    }

    public function testValidateSenderEmail()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"giftcard_recipient_name":"name",'
                . '"giftcard_sender_name":"name",'
                . '"giftcard_recipient_email":"email",'
                . '"giftcard_amount":5}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_setStrictProcessMode(true);
        $this->_runValidationWithExpectedException('Please specify a sender email.');
    }

    public function testValidate()
    {
        $this->_preConditions();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue('{}')
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;
        $this->_product->setCustomOptions($this->_customOptions);

        $this->_setStrictProcessMode(false);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Test _getCustomGiftcardAmount when rate is equal
     */
    public function testGetCustomGiftcardAmountForEqualRate()
    {
        $giftcardAmount = 11.54;
        $this->_mockModel(['_isStrictProcessMode', '_getAmountWithinConstraints']);
        $this->_preConditions();
        $this->_setStrictProcessMode(false);
        $this->_setGetGiftcardAmountsReturnArray();
        $this->_quoteItemOption->expects(
            $this->any()
        )->method(
            'getValue'
        )->will(
            $this->returnValue(
                '{"custom_giftcard_amount":' . $giftcardAmount . ',"giftcard_amount":"custom"}'
            )
        );
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_model->expects(
            $this->once()
        )->method(
            '_getAmountWithinConstraints'
        )->with(
            $this->equalTo($this->_product),
            $this->equalTo($giftcardAmount),
            $this->equalTo(false)
        )->will(
            $this->returnValue($giftcardAmount)
        );
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Test _getCustomGiftcardAmount when current currency rate is not equal
     */
    public function testGetCustomGiftcardAmountForDifferentRate()
    {
        $giftcardAmount = 11.54;
        $storeRate = 2;
        $this->_store->expects($this->any())->method('getCurrentCurrencyRate')->will($this->returnValue($storeRate));
        $this->_mockModel(['_isStrictProcessMode', '_getAmountWithinConstraints']);
        $this->_preConditions();
        $this->_setStrictProcessMode(false);
        $this->_setGetGiftcardAmountsReturnEmpty();
        $this->_quoteItemOption->expects($this->any())
            ->method('getValue')
            ->willReturn('{"custom_giftcard_amount":' . $giftcardAmount . ',"giftcard_amount":"custom"}');
        $this->serializer->expects($this->any())
            ->method('unserialize')
            ->willReturn(json_decode($this->_quoteItemOption->getValue(), true));
        $this->_model->expects($this->once())
            ->method('_getAmountWithinConstraints')
            ->with(
                $this->equalTo($this->_product),
                $this->equalTo($giftcardAmount / $storeRate),
                $this->equalTo(false)
            )
            ->willreturn($giftcardAmount);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Running validation with specified exception message
     *
     * @param string $exceptionMessage
     */
    protected function _runValidationWithExpectedException($exceptionMessage)
    {
        $this->_customOptions['info_buyRequest'] = $this->_quoteItemOption;

        $this->_product->setCustomOptions($this->_customOptions);

        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->_model->checkProductBuyState($this->_product);
    }

    /**
     * Set getGiftcardAmount return value to empty array
     */
    protected function _setGetGiftcardAmountsReturnEmpty()
    {
        $this->_product->expects($this->once())->method('getGiftcardAmounts')->will($this->returnValue([]));
    }

    /**
     * Set getGiftcardAmount return value
     */
    protected function _setGetGiftcardAmountsReturnArray()
    {
        $this->_product->expects($this->once())->method('getGiftcardAmounts')->willReturn([['website_value' => 5]]);
    }

    /**
     * Set strict mode
     *
     * @param bool $mode
     */
    protected function _setStrictProcessMode($mode)
    {
        $this->_model->expects($this->once())->method('_isStrictProcessMode')->will($this->returnValue((bool)$mode));
    }

    protected function _setAmountWithConstraints()
    {
        $this->_model->expects($this->once())->method('_getAmountWithinConstraints')->will($this->returnArgument(1));
    }

    public function testHasWeightTrue()
    {
        $this->assertTrue($this->_model->hasWeight(), 'This product has not weight, but it should');
    }
}
