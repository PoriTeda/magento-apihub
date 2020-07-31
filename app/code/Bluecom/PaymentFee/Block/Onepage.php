<?php

namespace Bluecom\PaymentFee\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\SubscriptionFrequency\Model\FrequencyFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Bundle\Model\Product\Type as ProductType;
use \Magento\Quote\Model\QuoteFactory as QuoteFactory;
use Riki\Loyalty\Model\RewardQuote;
use Riki\Checkout\Helper\Data;
use Riki\Subscription\Block\Frontend\Profile\Edit as SubscriptionProfileEdit;

class Onepage extends \Magento\Checkout\Block\Onepage
{
    /* @var \Riki\Checkout\Helper\Data */
    protected $_checkoutHelperData;

    /**
     * Helper
     *
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $_helperData;

    /**
     * Currency
     *
     * @var \Magento\Directory\Model\Currency
     */
    protected $_currency;

    /**
     * Current interface
     *
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * Config
     *
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * Payment fee
     *
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $_paymentFee;

    /**
     * Rewards Management
     *
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $_rewardQuoteFactory;

    /**
     * Session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Quote shipping address items
     *
     * @var array
     */
    protected $_quoteShippingAddressesItems;

    /**
     * Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Image
     *
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * Configuration pool
     *
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    protected $configurationPool;

    /**
     * Rule Factory
     *
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $_subCourseHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var FrequencyFactory
     */
    protected $_frequencyFactory;
    /**
     * @var JsonFactory
     */
    protected $_resultJson;

    /* @var \Magento\Quote\Model\QuoteFactory */
    protected $quoteFactory;
    /**
     * @var \Riki\Checkout\Api\ShippingAddressInterface
     */
    protected $shippingAddress;
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_promoItemHelper;

    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $stock;

    protected $frequencyHelper;

    protected $courseData;

    public function __construct(
        \Riki\Checkout\Helper\Data $checkoutHelperData,
        QuoteFactory $quoteRepository,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Bluecom\PaymentFee\Helper\Data $helper,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $paymentFee,
        \Magento\Directory\Model\Currency $currency,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        CustomerSession $customerSession,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        DateTime $dateTime,
        FrequencyFactory $frequencyFactory,
        JsonFactory $jsonFactory,
        \Riki\Checkout\Api\ShippingAddressInterface $shippingAddressInterface,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Wyomind\AdvancedInventory\Model\Stock $stock,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        $layoutProcessors = [],
        $data = []
    )
    {
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data);
        $this->_checkoutHelperData = $checkoutHelperData;
        $this->_helperData = $helper;
        $this->quoteFactory = $quoteRepository;
        $this->_currency = $currency;
        $this->_localeCurrency = $localeCurrency;
        $this->_paymentConfig = $paymentConfig;
        $this->_paymentFee = $paymentFee;
        $this->_rewardManagement = $rewardManagement;
        $this->customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->imageHelper = $imageHelper;
        $this->configurationPool = $configurationPool;
        $this->_ruleFactory = $ruleFactory;
        $this->_subCourseHelper = $subCourseHelper;
        $this->_courseFactory = $courseFactory;
        $this->_datetime = $dateTime;
        $this->_frequencyFactory = $frequencyFactory;
        $this->_resultJson = $jsonFactory;
        $this->_rewardQuoteFactory = $rewardQuoteFactory;
        $this->shippingAddress = $shippingAddressInterface;
        $this->_promoItemHelper = $promoItemHelper;
        $this->stock = $stock;
        $this->frequencyHelper = $frequencyHelper;
    }

    /**
     * Get current customer code
     *
     * @return bool|string
     */
    public function getCustomerCode()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->customerSession->getCustomer()->getData('consumer_db_id');
        }
        return false;
    }

    /**
     * Get loyalty customer point from session
     *
     * @return int
     */
    public function getCustomerRewardPoint()
    {
        return $this->_rewardManagement->getPointBalance($this->getCustomerCode());
    }

    /**
     * Get reward user setting
     *
     * @return int
     */
    public function getRewardUserSetting()
    {
        if (!$this->hasData('reward_user_setting')) {
            $quote = $this->_checkoutSession->getQuote();
            $rewardQuote = $this->_rewardQuoteFactory->create();
            $rewardQuote->load($quote->getId(), RewardQuote::QUOTE_ID);
            $setting = $this->_rewardManagement->getRewardUserSetting($this->getCustomerCode());
            if ($rewardQuote->getId()) {
                $this->setData('reward_user_setting', $rewardQuote->getRewardUserSetting());
                $this->setData('use_point_amount', $rewardQuote->getRewardUserRedeem());
            } else {
                $this->setData('reward_user_setting', $setting['use_point_type']);
                $this->setData('use_point_amount', $setting['use_point_amount']);
            }
            if ($this->getData('reward_user_setting') != RewardQuote::USER_USE_SPECIFIED_POINT) {
                $this->setData('use_point_amount', $setting['use_point_amount']);
            }
        }
        return (int)$this->getData('reward_user_setting');
    }

    /**
     * Get reward user redeem
     *
     * @return int
     */
    public function getRewardUserRedeem()
    {
        if (!$this->hasData('reward_user_redeem')) {
            $balance = $this->getCustomerRewardPoint();
            $redeem = $this->getData('use_point_amount');
            $this->setData('reward_user_redeem', min($redeem, $balance));
        }
        return (int)$this->getData('reward_user_redeem');
    }

    /**
     * Get multi checkout base url
     *
     * @return string
     */
    public function getMultiCheckoutBaseUrl()
    {
        return $this->getUrl('multicheckout');
    }

    /**
     * Get all payment fee codes
     *
     * @return array
     */
    public function getPaymentFeeCode()
    {
        $ruleApplied = $this->_checkoutSession->getQuote()->getAppliedRuleIds();
        $freeSurcharge = 0;
        if ($ruleApplied > 0) {
            $ruleModel = $this->_ruleFactory->create()->getCollection();
            $ruleModel->addFieldToFilter('rule_id', explode(',', $ruleApplied));
            foreach ($ruleModel as $rule) {
                if ($rule->getData('free_cod_charge') == 1) {
                    $freeSurcharge = 1;
                }
            }
        }
        $paymentMethod = $this->_paymentFee->getAllCodes();
        if ($freeSurcharge) {
            foreach ($paymentMethod as $code => $amount) {
                $paymentMethod[$code] = 0;
            }
        }
        return $paymentMethod;
    }

    /**
     * Get key code
     *
     * @return array
     */
    public function getKeyCode()
    {
        return $this->_paymentFee->getKeyCodes($this->getPaymentFeeCode());
    }

    /**
     * Get quote shipping addresses items
     *
     * @return array|string
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteShippingAddressesItems()
    {
        if ($this->_quoteShippingAddressesItems !== null) {
            return $this->_quoteShippingAddressesItems;
        }
        /**
         * Need to put try..catch for prevent some stupid case , like redis down or affect by any custom module by dump guy
         */
        try {
            /* @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->_checkoutSession->getQuote();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->_quoteShippingAddressesItems = null;
            throw $e;
        } catch (\Exception $e) {
            $this->_quoteShippingAddressesItems = null;
            throw $e;
        }

        $items = [];
        /**
         * Current customer
         *
         * @var \Magento\Customer\Model\Customer
         */
        $currentCustomer = $this->customerSession->getCustomer();

        /**
         * Default shipping address
         *
         * @var \Magento\Customer\Model\Address $defaultShippingAddress
         */
        $defaultShippingAddress = $currentCustomer->getDefaultShippingAddress(); // return false on new customer case

        if (!$defaultShippingAddress) { // incase customer does not set default shipping address , we will get the first address from customer

            $customerAddressCollection = $currentCustomer->getAddressesCollection();
            /* set page size to 1 for optimize performance */
            $customerAddressCollection->getSelect()->limit(1);
            try {
                if ($customerAddressCollection->getSize() >= 1) {
                    $defaultShippingAddress = $customerAddressCollection->getFirstItem(); // @codingStandardsIgnoreLine
                } else {
                    $defaultShippingAddress = false;
                }
            } catch (\Exception $exception) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__("Could not load address from customer"));
            }
        }

        $processedItemsId = [];

        /* if quote address already has item , we will ignore the below coding line */
        /** @var  $quoteAddressObject \Magento\Quote\Model\Quote\Address */
        foreach ($quote->getAddressesCollection() as $quoteAddressObject) {
            if ($quoteAddressObject->getAddressType() == 'billing') {
                continue 1;
            }
            if ($quoteAddressObject->getItemsCollection()->getSize() <= 0) {
                continue 1;
            }
            /** @var \Magento\Quote\Model\Quote\Address\Item $item */
            foreach ($quoteAddressObject->getItemsCollection() as $item) {
                /* don't check case configurable / bundle product  */
                if ($item->getParentItemId()) {
                    continue 2;
                }
                if ($item->getProduct()->getIsVirtual()) {
                    $item->setThumbnail(
                        $this->imageHelper->init(
                            $item->getProduct(),
                            'product_thumbnail_image'
                        )->getUrl()
                    );
                    $items[] = $item->toArray();
                    continue;
                }
                //set item id
                $item->setItemId($item->getQuoteItemId());
                $item->setCustomerAddressId($quoteAddressObject->getCustomerAddressId());
                $item->setOptions($this->getFormattedOptionValue($item));
                $item->setThumbnail(
                    $this->imageHelper->init(
                        $item->getProduct(),
                        'product_thumbnail_image'
                    )->getUrl()
                );
                $items[] = $item->toArray();

                $processedItemsId[] = $item->getQuoteItemId();
            }
        }

        // merge free gift item
        if (count($processedItemsId)) {
            foreach ($quote->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                if ($this->_promoItemHelper->isPromoItem($item)
                    && !in_array($item->getId(), $processedItemsId)
                ) {
                    $item->setThumbnail(
                        $this->imageHelper->init(
                            $item->getProduct(),
                            'product_thumbnail_image'
                        )->getUrl()
                    );
                    $items[] = $item->toArray();
                }
            }
        }

        if (\Zend_Validate::is($items, 'NotEmpty')) {
            /* need to collect total for updating row total */
            try {
                /* @var \Magento\Quote\Model\Quote $quoteFromRepository */
                $quoteFromRepository = $this->quoteFactory->create()->loadByIdWithoutStore($quote->getId());
                $quoteFromRepository->setData('is_multiple_shipping', $quote->getData('is_multiple_shipping'));
                $quoteFromRepository->getShippingAddress()->setCollectShippingRates(false);
                $quoteFromRepository->collectTotals();
                $quoteFromRepository->save();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Unable to recollect total , detail :" . $e->getMessage()));
            }
            $this->_quoteShippingAddressesItems = $items;
            return \Zend_Json::encode($this->_quoteShippingAddressesItems);
        } else {
            $items = [];
        }

        /**
         * Quote item
         *
         * @var \Magento\Quote\Model\Quote\Item
         */
        foreach ($quote->getAllItems() as $item) {
            /* don't check case configurable / bundle product  */
            if ($item->getParentItemId()) {
                continue;
            }

            if ($this->_promoItemHelper->isPromoItem($item) || $item->getIsRikiMachine() || $item->getPrizeId()) {
                $item->setThumbnail(
                    $this->imageHelper->init(
                        $item->getProduct(),
                        'product_thumbnail_image'
                    )->getUrl()
                );
                $items[] = $item->toArray();
                continue;
            }

            if ($item->getProduct()->getIsVirtual()) {
                $item->setThumbnail(
                    $this->imageHelper->init(
                        $item->getProduct(),
                        'product_thumbnail_image'
                    )->getUrl()
                );
                $items[] = $item->toArray();
                continue;
            }

            $parentItemId = null;
            if ($item->getQty() > 1
            ) {
                $unitQty = 1;
                if ('CS' == $item->getUnitCase()) {
                    $unitQty = ($item->getUnitQty() != null) ? $item->getUnitQty() : 1;
                }

                for ($itemIndex = 0, $itemQty = $item->getQty() / $unitQty; $itemIndex < $itemQty; $itemIndex++) {
                    if ($itemIndex == 0) {
                        $addressItem = $item;
                        $parentItemId = $item->getItemId();
                    } else {
                        $addressItem = clone $item;
                        $addressItem->setData('slip_parent_item_id', $parentItemId);
                        $addressItem->unsetData("item_id");
                    }
                    if($addressItem->getQty() != $unitQty) {
                        $addressItem->setQty($unitQty);
                    }

                    if ($defaultShippingAddress
                        && $defaultShippingAddress instanceof \Magento\Customer\Model\Address
                    ) {
                        $addressItem->setCustomerAddressId($defaultShippingAddress->getId());
                    }

                    $addressItem->setThumbnail(
                        $this->imageHelper->init(
                            $addressItem->getProduct(),
                            'product_thumbnail_image'
                        )->getUrl()
                    );
                    $addressItem->setOptions($this->getFormattedOptionValue($addressItem));
                    $addressItem->save(); // @codingStandardsIgnoreLine

                    if ($itemIndex > 0) {
                        $this->addChildProductWhenSlipBundleProduct($quote, $addressItem);
                    }
                    
                    $items[] = $addressItem->toArray();

                }
            } else {
                if ($defaultShippingAddress
                    && $defaultShippingAddress instanceof \Magento\Customer\Model\Address
                    && !$item->getCustomerAddressId() // prevent reset to default customer address
                ) {
                    $item->setCustomerAddressId($defaultShippingAddress->getId());
                }
                
                $item->setOptions($this->getFormattedOptionValue($item));
                $item->setThumbnail(
                    $this->imageHelper->init(
                        $item->getProduct(),
                        'product_thumbnail_image'
                    )->getUrl()
                );
                $items[] = $item->toArray();
            }
        }
        /* need to collect total for updating row total */
        try {
            /* @var \Magento\Quote\Model\Quote $quoteFromRepository */
            $quoteFromRepository = $this->quoteFactory->create()->loadByIdWithoutStore($quote->getId());
            $quoteFromRepository->setData('is_multiple_shipping', $quote->getData('is_multiple_shipping'));
            $quoteFromRepository->getShippingAddress()->setCollectShippingRates(false);
            $quoteFromRepository->collectTotals();
            $quoteFromRepository->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Unable to recollect total , detail :" . $e->getMessage()));
        }
        $this->_quoteShippingAddressesItems = $items;
        return \Zend_Json::encode($this->_quoteShippingAddressesItems);
    }

    /**
     * Add child product when slip
     *
     * @param $item
     *
     * @return void()
     */

    public function addChildProductWhenSlipBundleProduct($quote, $addressItem)
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        $arrChildItem = array();
        foreach ($quote->getAllItems() as $item) {
            /* @var \Magento\Quote\Model\Quote\Item $item */
            if ($item->getParentItemId()) {
                $arrChildItem['item'][$item->getProduct()->getId()][$item->getOptionByCode('selection_id')->getValue()] = $item;
                $arrChildItem['parentItemId'][] = $item->getParentItemId();
            }
        }
        /* @var $addressItem \Magento\Quote\Model\Quote\Item\Interceptor */
        if ($addressItem && $addressItem->getData('product_type') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            // Bundle Slip but not have child product.
            $arrChildProductIdOfThisItem = $this->getChildProductOfBundle($addressItem->getProduct());
            foreach ($arrChildProductIdOfThisItem as $selectionId => $childId) {
                if (in_array($childId, array_keys($arrChildItem['item']))) {
                    $parentItemId = $arrChildItem['item'][$childId][$selectionId]->getItemId();
                    /* @var \Magento\Quote\Model\Quote\Item $itemNeedAdd */
                    $itemNeedAdd = clone $arrChildItem['item'][$childId][$selectionId];
                    $itemNeedAdd->setData('slip_parent_item_id', $parentItemId);
                    $itemNeedAdd->unsetData("item_id");
                    if ($addressItem->getCustomerAddressId()
                        && $addressItem->getCustomerAddressId() instanceof \Magento\Customer\Model\Address
                    ) {
                        $itemNeedAdd->setCustomerAddressId($addressItem->getCustomerAddressId());
                    }
                    $itemNeedAdd->setThumbnail(
                        $this->imageHelper->init(
                            $itemNeedAdd->getProduct(),
                            'product_thumbnail_image'
                        )->getUrl()
                    );
                    $itemNeedAdd->setParentItemId($addressItem->getId());
                    $itemNeedAdd->setOptions($this->getFormattedOptionValue($itemNeedAdd));
                    $itemNeedAdd->save(); // @codingStandardsIgnoreLine
                }
            }
        }
    }

    /**
     * Get Child Product Of Bundle
     *
     * @param $product
     *
     * @return array
     */
    public function getChildProductOfBundle($product)
    {
        $arrResult = [];
        /* @var \Magento\Catalog\Model\Product $product */
        $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance(true)
            ->getSelectionsCollection($optionCollection->getAllIds(), $product);
        foreach ($selectionCollection as $section) {
            $arrResult[$section->getSelectionId()] = $section->getId();
        }
        return $arrResult;
    }

    /**
     * Retrieve formatted item options view
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $item item
     *
     * @return array
     */
    protected function getFormattedOptionValue($item)
    {
        $optionsData = [];
        $options = $this->configurationPool->getByProductType($item->getProductType())->getOptions($item);
        foreach ($options as $index => $optionValue) {
            /**
             * Config
             *
             * @var $helper \Magento\Catalog\Helper\Product\Configuration
             */
            $helper = $this->configurationPool->getByProductType('default');
            $params = [
                'max_length' => 55,
                'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
            ];
            $option = $helper->getFormattedOptionValue($optionValue, $params);
            $optionsData[$index] = $option;
            $optionsData[$index]['label'] = $optionValue['label'];
        }
        return $optionsData;
    }

    /**
     * Check allow edit Next DDate
     *
     * @return int
     */
    public function checkIsEditNextDDate()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            $subType = $model->getSubscriptionType();
            if ($subType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return 0;
            } else {
                $setting = array_map(function ($value) {
                    return $value ? 1 : 0;
                }, $model->getSettings());
                return $setting['is_allow_change_next_delivery'];
            }
        }
        return 1;
    }

    /**
     * Check Hanpukai subscription
     *
     * @return int (1 subscription is hanpukai | 0 subscription is not hanpukai)
     */
    public function isHanpukaiSubscription()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            $subType = $model->getSubscriptionType();
            if ($subType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * get Maximum order time of Hanpukai
     * @return int|mixed
     */
    public function getHanpukaiMaximumOrderTime() {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            return $model->getHanpukaiMaximumOrderTimes();
        }
        return 0;
    }

    /**
     * Check is subscription checkout
     *
     * @return int (1 is subscription | 0 not subscription
     */
    public function isSubscription()
    {
        $courseId = $this->_checkoutSession->getQuote()->getData('riki_course_id');
        if ($courseId) {
            return 1;

        }
        return 0;
    }

    /**
     * Exits Product Have Qty More Than One Hundred
     *
     * @return int (0 => not exist | 1 => exist)
     */
    public function existProductHaveQtyMoreThanOneHundred()
    {
        $quote = $this->_checkoutSession->getQuote();
        $existProductHaveQtyMoreThanOneHundred = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            if (strtoupper($item['case_display'])
                == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE ||
                strtoupper($item['unit_case'])
                == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $qtyShowInFo = $item->getQty() / $item->getUnitQty();
                if ($qtyShowInFo >= 100) {
                    $existProductHaveQtyMoreThanOneHundred = 1;
                    break;
                }
            } else {
                if ($item->getQty() >= 100) {
                    $existProductHaveQtyMoreThanOneHundred = 1;
                    break;
                }
            }
        }
        return $existProductHaveQtyMoreThanOneHundred;
    }

    /**
     * Is Allow Change Hanpukai 0 not allow change, 1 allow change, -1 not hanpukai
     *
     */
    public function isAllowChangeHanpukaiDeliveryDate()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($model->getSubscriptionType() == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                return $model->getHanpukaiDeliveryDateAllowed();
            }
        }
        return -1;
    }

    public function getHanpukaiDeliveryDateRuleConfig()
    {
        $result = array();
        $isAllowChangeHanpukaiDeliveryDate = $this->isAllowChangeHanpukaiDeliveryDate();
        $course = $this->getCourseData();
        if ($course instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($isAllowChangeHanpukaiDeliveryDate == 1) {
                $result['hanpukai_delivery_date_from'] = $this->formatDateCustom($course->getData('hanpukai_delivery_date_from'));
                $result['hanpukai_delivery_date_to'] = $this->formatDateCustom($course->getData('hanpukai_delivery_date_to'));
                $result['hanpukai_first_delivery_date'] = '';
            } elseif ($isAllowChangeHanpukaiDeliveryDate == 0) {
                $result['hanpukai_first_delivery_date'] = $this->formatDateCustom($course->getData('hanpukai_first_delivery_date'));
                $result['hanpukai_delivery_date_from'] = '';
                $result['hanpukai_delivery_date_to'] = '';
            } else {
                $result['hanpukai_first_delivery_date'] = '';
                $result['hanpukai_delivery_date_from'] = '';
                $result['hanpukai_delivery_date_to'] = '';
            }
            return $result;
        }
    }

    public function formatDateCustom($stringDate)
    {
        return $this->_datetime->date('Y-m-d', strtotime($stringDate));
    }

    public function getFrequency()
    {
        $frequencyId = $this->_checkoutSession->getQuote()->getData('riki_frequency_id');
        $json = [];
        if ($frequencyId) {
            $frequencyModel = $this->_frequencyFactory->create()->load($frequencyId);
            if ($frequencyModel->getId()) {
                $json = [
                    'interval' => $frequencyModel->getData('frequency_interval'),
                    'unit' => $frequencyModel->getData('frequency_unit')
                ];
            }
        }
        if (!isset($json['interval'])) {
            $json['interval'] = '';
        }
        if (!isset($json['unit'])) {
            $json['unit'] = '';
        }
        return $json;
    }

    public function getFrequencyString()
    {
        $frequencyId = $this->_checkoutSession->getQuote()->getData('riki_frequency_id');
        return $this->frequencyHelper->getFrequencyString($frequencyId);
    }

    public function saveItemAddressInformation()
    {
        $dataString = '';
        try {
            $quote = $this->_checkoutSession->getQuote();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
        $cartId = $quote->getId();

        // Current customer
        $currentCustomer = $this->customerSession->getCustomer();

        /**
         * Default shipping address
         *
         * @var \Magento\Customer\Model\Address $defaultShippingAddress
         */
        $defaultShippingAddress = $currentCustomer->getDefaultShippingAddress(); // return false on new customer case

        if (!$defaultShippingAddress) {
            // incase customer does not set default shipping address , we will get the first address from customer
            $customerAddressCollection = $currentCustomer->getAddressesCollection();
            /* set page size to 1 for optimize performance */
            $customerAddressCollection->getSelect()->limit(1);
            try {
                if ($customerAddressCollection->getSize() >= 1) {
                    $defaultShippingAddress = $customerAddressCollection->getFirstItem(); // @codingStandardsIgnoreLine
                } else {
                    $defaultShippingAddress = false;
                }
            } catch (\Exception $exception) {
                throw new \Magento\Framework\Exception\NoSuchEntityException(__("Could not load address from customer"));
            }
        }
        if ($defaultShippingAddress && $defaultShippingAddress instanceof \Magento\Customer\Model\Address) {
            $defaultAddressId = $defaultShippingAddress->getId();
            foreach ($quote->getAllVisibleItems() as $item) {
                // don't check case configurable / bundle product
                if ($item->getParentItemId()) {
                    continue;
                }
                if ($item->getProduct()->getIsVirtual()) {
                    continue;
                }
                $dataString .= 'cart[' . $item->getId() . '][address]=' . $defaultAddressId . '&';
            }
            return $this->shippingAddress->saveItemAddressInformation($cartId, $dataString);
        }

        return false;
    }

    /**
     * Get Course Data
     * @return bool|\Riki\SubscriptionCourse\Model\Course
     */
    public function getCourseData() {
        $courseId = $this->_checkoutSession->getQuote()->getData('riki_course_id');
        if ($courseId) {
            if (isset($this->courseData[$courseId])) {
                return $this->courseData[$courseId];
            }
            $model = $this->_courseFactory->create()->load($courseId);
            if ($model->getId()) {
                $this->courseData[$courseId] = $model;
                return $model;
            }
        }
        return false;
    }
    /**
     * Get course name
     * @return string
     */
    public function getCourseName()
    {

        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            return $model->getData('course_name');
        }
        return '';
    }

    /**
     * Get minimum_order_times
     * @return string
     */
    public function getMinimumOrderTimes()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($model->getData('is_allow_cancel_from_frontend') == 1) {
                return $model->getData('minimum_order_times');
            }
        }
        return '';
    }

    /**
     * Get penalty_fee
     * @return string
     */
    public function getPenaltyFee()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            return $model->getData('penalty_fee');
        }
        return '';
    }

    /**
     * get Order total minimum amount threshold - custom order
     * @return array
     */
    public function getOrderTotalMinimumAmount()
    {
        $model = $this->getCourseData();
        if ($model instanceof \Riki\SubscriptionCourse\Model\Course) {
            $condition = $model->getData('oar_condition_serialized');
            if ($condition) {
                $serializeData = json_decode($condition, true);
                if(isset($serializeData['minimum']['amounts']) && $serializeData['minimum']['option'] == 2) {
                    return json_encode($serializeData['minimum']['amounts']);
                } else {
                    return  $serializeData['minimum']['amount'];
                }
            }
        }
        return '';
    }

    /**
     * Get select box hanpukai change qty
     *
     * @return json object
     */
    public function getSelectBoxHanpukai()
    {
        $arr = [];
        for ($i=1; $i < 31; $i++) {
            $arr[] = $i;
        }
        return $arr;
    }

    /**
     * Get factor in case hanpukai
     *
     * @return int
     */
    public function getFactor()
    {
        $quote = $this->_checkoutSession->getQuote();
        $courseId = $this->_checkoutSession->getQuote()->getData('riki_course_id');
        if ($courseId) {
            $originData = $this->_checkoutHelperData->getArrProductFirstDeliveryHanpukai($courseId);
            $cartData = $this->_checkoutHelperData->makeCartDataFromQuote($quote);
            $factor = $this->_checkoutHelperData->calculateFactor($originData, $cartData, $quote);
            if ($factor === false) {
                return 1;
            }
            return $factor;
        }
        return 1;
    }

    /**
     * Link edit home no company name
     *
     * @return string
     */
    public function getUrlEditHomeNoCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            SubscriptionProfileEdit::ADDRESS_LINK_EDIT_HOME_NO_COMPANY) . $this->_urlBuilder->getCurrentUrl();
    }


    /**
     * Link edit home have company name
     *
     * @return string
     */
    public function getUrlEditHomeHaveCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            SubscriptionProfileEdit::ADDRESS_LINK_EDIT_HOME_HAVE_COMPANY) . $this->_urlBuilder->getCurrentUrl();
    }


    /**
     * Link edit ambassador company
     *
     * @return string
     */
    public function getUrlEditCompany()
    {
        return $this->_subCourseHelper->getStoreConfig(
            SubscriptionProfileEdit::ADDRESS_LINK_EDIT_AMBASSADOR_COMPANY) . $this->_urlBuilder->getCurrentUrl();
    }

    /**
     * Link edit ambassador company
     *
     * @return string
     */
    public function getWrappingServicesLink()
    {
        return $this->_subCourseHelper->getStoreConfig('wrapping_services_link/wrapping_services_group/wrapping_services_input');
    }
}