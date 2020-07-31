<?php
namespace Riki\Subscription\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Riki\Subscription\Model\Frequency\Frequency;
use Magento\Catalog\Model\Product as CatalogModelProduct;
use Symfony\Component\Config\Definition\Exception\Exception;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollection;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Cache on code level
     * product.id
     * @var array
     */
    protected $simpleLocalStorage = [];

    /**
     * @var \Magento\Framework\App\Resource
     */
    protected $resource;

    protected $collectionProduct;

    protected $collectionProductCart;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $_addressRenderer;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    protected $_extensibleDataObjectConverter;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imgHelper;

    /**
     * @var CatalogModelProduct\Media\Config
     */
    protected $_mediaConfig;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_salesConnection;

    /**
     * @var \Riki\AdvancedInventory\Model\OutOfStockFactory
     */
    protected $outOfStockFactory;

    /**
     * @var \Magento\CatalogInventory\Model\StockRegistry
     */
    protected $stockRegistry;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutDataHelper;

    /**
     * @var \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    private $adjustmentCalculator;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * list of calculated price product
     *
     * @var array
     */
    protected $calculatedProductPrice = [];

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory
     * @param \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $collectionProductCart
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Magento\Catalog\Helper\Image $imgHelper
     * @param CatalogModelProduct\Media\Config $mediaConfig
     * @param \Riki\AdvancedInventory\Model\OutOfStockFactory $outOfStockFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Checkout\Helper\Data $checkoutHelperData
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionProductFactory,
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $collectionProductCart,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Magento\Catalog\Helper\Image $imgHelper,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Riki\AdvancedInventory\Model\OutOfStockFactory $outOfStockFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Checkout\Helper\Data $checkoutHelperData,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_backendUrl = $backendUrl;
        $this->_customerCollection = $collectionFactory;
        $this->objectManager = $objectManager;
        $this->categoryFactory = $categoryFactory;
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection('core_write');
        $this->_salesConnection = $this->resource->getConnection('sales');
        $this->collectionProduct = $collectionProductFactory;
        $this->collectionProductCart = $collectionProductCart;
        $this->_paymentConfig = $paymentConfig;
        $this->_addressRenderer = $addressRenderer;
        $this->_extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->_imgHelper = $imgHelper;
        $this->_mediaConfig = $mediaConfig;
        $this->outOfStockFactory = $outOfStockFactory;
        $this->stockRegistry = $stockRegistry;
        $this->profileRepository = $profileRepository;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutDataHelper = $checkoutHelperData;
        $this->adjustmentCalculator = $adjustmentCalculator;
        $this->priceCurrency = $priceCurrency;
        $this->localeFormat = $localeFormat;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Pricing\Adjustment\CalculatorInterface
     */
    public function getAdjustmentCalculator()
    {
        return $this->adjustmentCalculator;
    }

    /**
     * @return mixed
     */
    public function getAllFrequency()
    {
        if (! isset($this->simpleLocalStorage['all_frequency'])) {
            $select = $this->connection->select()
                ->from($this->resource->getTableName(Frequency::TABLE));

            $arrData = $this->_salesConnection->fetchAll($select);


            $arrResult = [];
            if (! empty($arrData)) {
                foreach ($arrData as $index => $arrValue) {
                    $arrResult[$arrValue['frequency_id']] = [$arrValue['frequency_interval'], $arrValue['frequency_unit']];
                }
            }

            $this->simpleLocalStorage['all_frequency'] = $arrResult;
        }

        return $this->simpleLocalStorage['all_frequency'];
    }

    public function getAllPaymentMethods()
    {
        $payments = $this->_paymentConfig->getActiveMethods();

        return $payments;
    }


    /**
     * Prevent call many time when load product
     *
     * @param $id
     * @return mixed
     */
    public function loadProductWithCache($id)
    {
        if (! isset($this->simpleLocalStorage['product'][$id])) {
            $productFactory = $this->objectManager->create('\Magento\Catalog\Model\ProductFactory');
            $product = $productFactory->create();
            $product->load($id);

            $this->simpleLocalStorage['product'][$id] = $product;
        }

        return $this->simpleLocalStorage['product'][$id];
    }


    public function getFrequencyIdByUnitAndInterval($unit, $interval)
    {
        $arrAllFrequency = $this->getAllFrequency();

        $frequency_unit = $unit;
        $frequency_interval = $interval;

        $value = 0;

        foreach ($arrAllFrequency as $fid => $arrF) {
            if ($arrF[0] == $frequency_interval && $arrF[1] === $frequency_unit) {
                $value = $fid;
            }
        }

        return $value;
    }

    public function getBundleMaximumPrice(CatalogModelProduct $product)
    {
        $finalPrice =  $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $amount = ($finalPrice instanceof \Magento\Catalog\Pricing\Price\FinalPrice)
            ? $finalPrice->getAmount()
            : $product->getFinalPrice();

        $amount = ($amount instanceof \Magento\Framework\Pricing\Amount\AmountInterface)
            ? filter_var($this->_checkoutDataHelper->formatPrice($amount->getValue()), FILTER_SANITIZE_NUMBER_FLOAT)
            : $amount;


        return $amount;
    }

    /**
     * Get free gift from simulator order
     *
     * @param \Riki\Subscription\Model\Emulator\Order $simulatorOrder
     * @param bool $flatData
     * @return array
     */
    public function getFreeGifts($simulatorOrder, $flatData = false)
    {
        $freeGifts = [];
        if (!$simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $freeGifts;
        }
        /** @var \Riki\Subscription\Model\Emulator\Order\Item $orderItem */
        foreach ($simulatorOrder->getAllItems() as $orderItem) {
            $buyRequest = $orderItem->getBuyRequest()->getData();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                if ($flatData) {
                    $flatItem = $orderItem->getData();
                    $flatItem['address_formatted'] = $this->getShippingAddressFormattedText($orderItem);
                    $flatItem['product_data'] = $orderItem->getProduct()->getData();
                    $flatItem['product_data']['thumbnail'] = $this->getProductImagesProfile($orderItem->getProduct());
                    $freeGifts[] = $flatItem;
                } else {
                    $freeGifts[] = $orderItem;
                }
            }
        }
        return $freeGifts;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductImagesProfile($product)
    {
        $origImageHelper = $this->_imgHelper->init($product, 'product_listing_thumbnail_preview');
        return $origImageHelper->getUrl();
    }

    /**
     * Getter for shipping address by format
     *
     * @param \Riki\Subscription\Model\Emulator\Order\Item $orderItem
     * @return string
     */
    public function getShippingAddressFormattedText($orderItem)
    {
        $objAddress = $orderItem->getOrder()->getShippingAddress();
        if (!$objAddress instanceof \Magento\Sales\Model\Order\Address) {
            return '';
        }
        $address = [
            $objAddress->getStreetLine(1),
            $objAddress->getCity(),
            $objAddress->getPostcode(),
            $objAddress->getRegion(),
            $objAddress->getRegionId(),
        ];
        //return $this->addressRenderer->format($shippingAddress, 'text');
        return implode(', ', $address);
    }

    /**
     * Get products out-off stock of a profile
     *
     * @param $profileId
     * @return array
     */
    public function getAllProductOutOfStockInProfile($profileId){
        $outOfStockModel = $this->outOfStockFactory->create()->getCollection();
        $outOfStockModel->addFieldToFilter('subscription_profile_id', $profileId);
        $outOfStockModel->addFieldToFilter('generated_order_id', ['null' => true]);
        $productOutOffStock = [];
        foreach ($outOfStockModel->getItems() as $product) {
            $productOutOffStock[] = $product->getData('product_id');
        }
        return $productOutOffStock;
    }

    /**
     * Get products stock level
     *
     * @param $profileId
     * @return array
     */
    public function getStockProductLevel($simulatorOrder){

        $stockLevelProduct = [];
        if (!$simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $stockLevelProduct;
        }

        foreach ($simulatorOrder->getAllItems() as $orderItem) {
            $productId = $orderItem->getProduct()->getId();
            if ($productId) {
                $availableQty = $this->stockRegistry->getStockItem($productId);
                $productQty = !empty($availableQty) && !empty($availableQty->getQty()) ? $availableQty->getQty() : 0;
                $stockLevelProduct[$productId] = $productQty;
            }
        }
        return $stockLevelProduct;
    }


    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }
    /**
     * Get subscription course_id and frequency_id from old quote when edit subscription order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getSubscriptionInfo(\Magento\Sales\Model\Order $order) {
        $quoteId = $order->getQuoteId();
        $quoteRepository = $this->quoteRepository->get($quoteId);
        $result = [];
        if ($quoteRepository->getId()) {
            if ($quoteRepository->getRikiCourseId()) {
                $result['course_id'] = $quoteRepository->getRikiCourseId();
            }
            if ($quoteRepository->getRikiFrequencyId()) {
                $result['frequency_id'] = $quoteRepository->getRikiFrequencyId();
            }
        } else {
            $subscriptionProfileId = $order->getSubscriptionProfileId();
            $profileModel = $this->profileRepository->get($subscriptionProfileId);
            if ($profileModel->getProfileId()) {
                $result['course_id'] = $profileModel->getCourseId();
                $subscriptionFrequencyInterval = $profileModel->getFrequencyInterval();
                $subscriptionFrequencyUnit = $profileModel->getFrequencyUnit();
                $result['frequency_id'] = $this->getFrequencyIdByUnitAndInterval($subscriptionFrequencyUnit, $subscriptionFrequencyInterval);
            }
        }
        return $result;
    }

    /**
     * get product price in profile edit page
     *
     * @param CatalogModelProduct $product
     * @param $qty
     * @return float
     */
    public function getProductPriceInProfileEditPage(\Magento\Catalog\Model\Product $product, $qty)
    {
        $request = $this->dataObjectFactory->create()->addData(['qty' => $product->getQty()]);
        $product->getTypeInstance()->prepareForCart($request, $product);

        $amount = $this->adjustmentCalculator->getAmount(
            $product->getFinalPrice($qty),
            $product
        )->getValue();

        $amount = $this->priceCurrency->format(
            $amount,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION
        );
        return $this->localeFormat->getNumber($amount);
    }

    /**
     * @param $profileId
     * @param int $time
     * @return \Magento\Sales\Model\Order|null
     */
    public function getProfileOrderAtSpecificTime($profileId, $time = 1)
    {
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
        $orderCollection = $this->orderCollectionFactory->create();

        $order = $orderCollection->addFieldToFilter('subscription_profile_id', $profileId)
            ->addFieldToFilter('subscription_order_time', $time)
            ->addFieldToFilter('state', ['neq' => \Magento\Sales\Model\Order::STATE_CANCELED])
            ->setOrder('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->setPageSize(1)
            ->getFirstItem();

        if ($order && $order->getId()) {
            return $order;
        }

        return null;
    }
}
