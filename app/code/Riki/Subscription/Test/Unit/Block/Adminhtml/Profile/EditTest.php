<?php

namespace Riki\Subscription\Test\Unit\Block\Adminhtml\Profile;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Riki\Subscription\Block\Adminhtml\Profile\Edit
     */
    protected $object;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $pageConfig;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $pageTitleMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentFeeHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $subscriptionHelperData;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperProfile;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerAddressCollectionFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperImage;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $rewardManagement;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $deliveryDate;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mediaConfig;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $wrappingRepository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $giftWrappingData;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $taxCalculation;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $localeInterface;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $simulator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $stockData;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $deliveryHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $courseHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $subHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $subPageHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $calculateDeliveryDate;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $extensibleDataObjectConverter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $addressRegistry;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $freeGift;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $disengageReason;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $timezone;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $productCartFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerReprository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $mageCustomerRepository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $pagentHistory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $backOrderHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $customerAddressHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $addressRepository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $profileSessionHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $pointOfSale;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $helperStockpoint;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $buildStockPointPostData;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $validateStockPointProduct;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $frequencyHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $regionFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $regionDataFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $regionHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $courseRepository;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $promoHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $profileIndexerHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $reasonModel;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $questionCollectionFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $choiceCollectionFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $answersCollectionFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $replyCollectionFactory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $profileModel;

    public function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->paymentFeeHelper = $this->getMockBuilder('Bluecom\PaymentFee\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->subscriptionHelperData = $this->getMockBuilder('Riki\Subscription\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->getMockBuilder('\Magento\Framework\View\Page\Config')
            ->disableOriginalConstructor()
            ->getMock();
        /**
         * @var \Magento\Framework\View\Page\Title
         */
        $titleMock = $this->getMockBuilder('\Magento\Framework\View\Page\Title')
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig->expects($this->once())
            ->method('getTitle')
            ->willReturn($titleMock);

        $titleMock->expects($this->once())
            ->method('set')
            ->with(__('Subscription Edit'))
            ->willReturnSelf();

        $context = $this->objectManager->getObject(
            'Magento\Backend\Block\Template\Context',
            [
                'pageConfig' => $this->pageConfig
            ]
        );

        $this->registry = $this->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperProfile = $this->getMockBuilder('Riki\Subscription\Helper\Profile\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerAddressCollectionFactory = $this->getMockBuilder('Magento\Customer\Model\ResourceModel\Address\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->helperImage = $this->getMockBuilder('Magento\Catalog\Helper\Image')
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardManagement = $this->getMockBuilder('Riki\Loyalty\Model\RewardManagement')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mediaConfig = $this->getMockBuilder('Magento\Catalog\Model\Product\Media\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryDate = $this->getMockBuilder('Riki\DeliveryType\Model\DeliveryDate')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentFeeHelper = $this->getMockBuilder('Bluecom\PaymentFee\Model\PaymentFeeFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerFactory = $this->getMockBuilder('Magento\Customer\Model\CustomerFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productReprository = $this->getMockBuilder('Magento\Catalog\Model\ProductRepository')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->wrappingRepository = $this->getMockBuilder('Magento\GiftWrapping\Api\WrappingRepositoryInterface')
            ->getMockForAbstractClass();
        $this->giftWrappingData = $this->getMockBuilder('Magento\GiftWrapping\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageFactory = $this->getMockBuilder('Magento\GiftMessage\Model\MessageFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->taxCalculation = $this->getMockBuilder('Magento\Tax\Model\TaxCalculation')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeInterface = $this->getMockBuilder('Magento\Framework\Locale\FormatInterface')
            ->getMockForAbstractClass();
        $this->simulator = $this->getMockBuilder('Riki\Subscription\Helper\Order\Simulator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockData = $this->getMockBuilder('Riki\ProductStockStatus\Helper\StockData')
            ->disableOriginalConstructor()
            ->getMock();
        $this->deliveryHelper = $this->getMockBuilder('Riki\DeliveryType\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->courseHelper = $this->getMockBuilder('Riki\SubscriptionCourse\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->subHelper = $this->getMockBuilder('Riki\Subscription\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->subPageHelper = $this->getMockBuilder('Riki\SubscriptionPage\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calculateDeliveryDate = $this->getMockBuilder('Riki\Subscription\Helper\CalculateDeliveryDate')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extensibleDataObjectConverter = $this->getMockBuilder('Magento\Framework\Api\ExtensibleDataObjectConverter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressRegistry = $this->getMockBuilder('Magento\Customer\Model\AddressRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->freeGift = $this->getMockBuilder('Riki\Subscription\Model\Profile\FreeGift')
            ->disableOriginalConstructor()
            ->getMock();
        $this->disengageReason = $this->getMockBuilder('Riki\SubscriptionProfileDisengagement\Model\Config\Source\Reason')
            ->disableOriginalConstructor()
            ->getMock();
        $this->timezone = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\Timezone')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productCartFactory = $this->getMockBuilder('Riki\Subscription\Model\ProductCart\ProductCartFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->customerReprository = $this->getMockBuilder('Riki\Customer\Model\CustomerRepository')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->mageCustomerRepository = $this->getMockBuilder('Magento\Customer\Api\CustomerRepositoryInterface')
            ->getMockForAbstractClass();
        $this->pagentHistory = $this->getMockBuilder('Bluecom\Paygent\Model\PaygentHistory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->backOrderHelper = $this->getMockBuilder('Riki\BackOrder\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerAddressHelper = $this->getMockBuilder('Riki\Customer\Helper\Address')
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressRepository = $this->getMockBuilder('Magento\Customer\Api\AddressRepositoryInterface')
            ->getMockForAbstractClass();
        $this->profileSessionHelper = $this->getMockBuilder('Riki\Subscription\Helper\Profile\ProfileSessionHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->pointOfSale = $this->getMockBuilder('Riki\PointOfSale\Model\Config\Source\PointOfSale')
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder('Magento\Framework\Api\SearchCriteriaBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperStockpoint = $this->getMockBuilder('Riki\Subscription\Helper\StockPoint\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildStockPointPostData = $this->getMockBuilder('Riki\StockPoint\Api\BuildStockPointPostDataInterface')
            ->getMockForAbstractClass();
        $this->validateStockPointProduct = $this->getMockBuilder('Riki\StockPoint\Helper\ValidateStockPointProduct')
            ->disableOriginalConstructor()
            ->getMock();
        $this->frequencyHelper = $this->getMockBuilder('Riki\SubscriptionFrequency\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionFactory = $this->getMockBuilder('Magento\Directory\Model\RegionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->regionDataFactory = $this->getMockBuilder('Magento\Customer\Api\Data\RegionInterfaceFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->regionHelper = $this->getMockBuilder('Riki\Customer\Helper\Region')
            ->disableOriginalConstructor()
            ->getMock();
        $this->courseRepository = $this->getMockBuilder('Riki\SubscriptionCourse\Api\CourseRepositoryInterface')
            ->getMockForAbstractClass();
        $this->promoHelper = $this->getMockBuilder('Riki\Promo\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->profileIndexerHelper = $this->getMockBuilder('Riki\Subscription\Helper\Indexer\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->reasonModel = $this->getMockBuilder('Riki\SubscriptionProfileDisengagement\Model\Reason')
            ->disableOriginalConstructor()
            ->getMock();
        $this->questionCollectionFactory = $this->getMockBuilder('Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->choiceCollectionFactory = $this->getMockBuilder('Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->answersCollectionFactory = $this->getMockBuilder('Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->replyCollectionFactory = $this->getMockBuilder('Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->profileModel = $this->getMockBuilder('Riki\Subscription\Model\Profile\Profile')
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->disableOriginalConstructor()->getMock();
        $this->pageTitleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->object = $this->objectManager->getObject(\Riki\Subscription\Block\Adminhtml\Profile\Edit::class,
            [
                'context' => $context,
                'registry' => $this->registry,
            ]);

    }

    public function testCheckSimulatorOrderDataWithExistProfileId()
    {
        $simulatorOrderMock = [
            'shosha_business_code' => "0311000001",
             'is_oos_order' => null,
             'profile_id' => "2001911920",
             'profile_assigned_warehouse_id' => null,
             'allow_choose_delivery_date' => null,
             'is_generate' => 1,
             'reward_points_balance' => "3138",
             'tax_for_authority' => 243.0,
             'gift_message_id' => null,
             'created_by' => "james",
             'state' => "processing",
             'status' => "waiting_for_shipping",
             'store_name' => "EC Site\nEC Store\nEC View",
             'total_item_count' => 2,
             'protect_code' => "b7665fcfcf1b0b1343e233beec6a9054",
             'increment_id' => "51000799071",
             'entity_id' => "1",
             'id' => "1",
        ];
        $productDataArr = [
            'sku' => 'sku123',
            'name' => "Automation_product3",
            'description' => null,
            'qty_ordered' => 1.0,
            'quote_item_id' => "1",
            'is_virtual' => false,
            'original_price' => 1840.0,
            'price' => 1840.0,
            'base_price' => 1840.0,
            'tax_percent' => 8.0,
            'tax_amount' => 147.0,
            'row_total' => 1840.0,
            'base_original_price' => "1840.0000",
            'base_tax_amount' => 147.0,
            'base_row_total' => 1840.0
        ];
        $objectSessionProfileMock = false;

        $optionObjectMock = $this->objectManager->getObject(\Riki\Subscription\Model\Emulator\Order\Item::class, []);
        $allItemsMock[] = $optionObjectMock->setData($productDataArr);

        $result = $this->object->checkSimulatorOrderData($simulatorOrderMock, $allItemsMock, $objectSessionProfileMock);
        $this->assertEquals('', $result);
    }

    public function testCheckSimulatorOrderDataWithNotExistProfileId()
    {
        $simulatorOrderMock = [
            'shosha_business_code' => "0311000001",
            'is_oos_order' => null,
            'profile_id' => "2002398785",
            'profile_assigned_warehouse_id' => null,
            'allow_choose_delivery_date' => null,
            'is_generate' => 1,
            'reward_points_balance' => "3138",
            'tax_for_authority' => 243.0,
            'gift_message_id' => null,
            'created_by' => "james",
            'state' => "processing",
            'status' => "waiting_for_shipping",
            'store_name' => "EC Site\nEC Store\nEC View",
            'total_item_count' => 2,
            'protect_code' => "b7665fcfcf1b0b1343e233beec6a9054",
            'increment_id' => "51000799071",
            'entity_id' => "1",
            'id' => "1",
        ];
        $productDataArr = [
            'sku' => 'sku123',
            'name' => "Automation_product3",
            'description' => null,
            'qty_ordered' => 1.0,
            'quote_item_id' => "1",
            'is_virtual' => false,
            'original_price' => 1840.0,
            'price' => 1840.0,
            'base_price' => 1840.0,
            'tax_percent' => 8.0,
            'tax_amount' => 147.0,
            'row_total' => 1840.0,
            'base_original_price' => "1840.0000",
            'base_tax_amount' => 147.0,
            'base_row_total' => 1840.0
        ];
        $objectSessionProfileMock = false;

        $optionObjectMock = $this->objectManager->getObject(\Riki\Subscription\Model\Emulator\Order\Item::class, []);
        $allItemsMock[] = $optionObjectMock->setData($productDataArr);

        $result = $this->object->checkSimulatorOrderData($simulatorOrderMock, $allItemsMock, $objectSessionProfileMock);
        $this->assertEquals('', $result);
    }

    public function testCheckSimulatorOrderDataWithNotEmptyObjectSession()
    {
        $simulatorOrderMock = [
            'shosha_business_code' => "0311000001",
            'is_oos_order' => null,
            'profile_id' => "2002398785",
            'profile_assigned_warehouse_id' => null,
            'allow_choose_delivery_date' => null,
            'is_generate' => 1,
            'reward_points_balance' => "3138",
            'tax_for_authority' => 243.0,
            'gift_message_id' => null,
            'created_by' => "james",
            'state' => "processing",
            'status' => "waiting_for_shipping",
            'store_name' => "EC Site\nEC Store\nEC View",
            'total_item_count' => 2,
            'protect_code' => "b7665fcfcf1b0b1343e233beec6a9054",
            'increment_id' => "51000799071",
            'entity_id' => "1",
            'id' => "1",
        ];
        $productDataArr = [
            'sku' => 'sku123',
            'name' => "Automation_product3",
            'description' => null,
            'qty_ordered' => 1.0,
            'quote_item_id' => "1",
            'is_virtual' => false,
            'original_price' => 1840.0,
            'price' => 1840.0,
            'base_price' => 1840.0,
            'tax_percent' => 8.0,
            'tax_amount' => 147.0,
            'row_total' => 1840.0,
            'base_original_price' => "1840.0000",
            'base_tax_amount' => 147.0,
            'base_row_total' => 1840.0
        ];
        $objectProductDataArr = [
                'cart_id' => "5796212",
                'profile_id' => "2001911920",
                'qty' => "1",
                'product_type' => "simple",
                'product_id' => "5903",
                'product_options' => "{}",
                'parent_item_id' => "0",
                'created_at' => "2020-05-15 09:34:06",
                'billing_address_id' => "1177001",
                'shipping_address_id' => "1177001",
                'updated_at' => "2020-05-15 09:34:06",
                'gw_used' => "0",
                'delivery_date' => "2020-08-15",
                'original_delivery_date' => null,
                'delivery_time_slot' => null,
                'original_delivery_time_slot' => null,
                'unit_case' => "EA",
                'unit_qty' => "1",
                'gw_id' => null,
                'gift_message_id' => null,
                'old_product_id' => null,
                'is_skip_seasonal' => null,
                'skip_from' => null,
                'skip_to' => null,
                'is_spot' => "0",
                'is_addition' => null,
                'stock_point_discount_rate' => null
        ];

        $optionObjectMock = $this->objectManager->getObject(\Riki\Subscription\Model\Emulator\Order\Item::class, []);
        $allItemsMock[] = $optionObjectMock->setData($productDataArr);

        $objectSessionMock = $this->objectManager->getObject(\Magento\Framework\DataObject::class, []);
        $allObjectItemMock[5903] = $objectSessionMock->setData($objectProductDataArr);

        $result = $this->object->checkSimulatorOrderData($simulatorOrderMock, $allItemsMock, $allObjectItemMock);
        $this->assertEquals('', $result);
    }
}
