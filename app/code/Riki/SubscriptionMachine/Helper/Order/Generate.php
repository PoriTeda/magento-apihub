<?php

namespace Riki\SubscriptionMachine\Helper\Order;

use Magento\Framework\App\Helper\Context;
use Riki\SubscriptionMachine\Model\MachineConditionRuleFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use \Riki\SubscriptionMachine\Model\MachineConditionRule;
use \Riki\Customer\Model\StatusMachine;
use Riki\SubscriptionMachine\Model\ResourceModel\MachineConditionRule\Collection as MachineConditionRuleCollection;

class Generate extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MachineCustomerFactory
     */
    protected $machineCustomer;

    /**
     * @var \Riki\SubscriptionMachine\Model\MachineSkusFactory
     */
    protected $machineSkus;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership
     */
    protected $membership;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var  \Riki\Customer\Model\CustomerRepository $customerRepository
     */
    protected $rikiCustomerRepository;

    /**
     * @var MachineConditionRuleFactory
     */
    protected $machineConditionRule;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Subscription\Helper\Order\Email
     */
    protected $helperSendEmail;

    /**
     * @var \Riki\Subscription\Logger\LoggerOrder
     */
    protected $loggerOrder;

    /**
     * @var \Riki\Subscription\Logger\LoggerFreeMachine
     */
    protected $loggerFreeMachine;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * @var \Riki\ShipLeadTime\Model\LeadtimeFactory
     */
    protected $leadtimeFactory;

    /**
     * @var \Riki\ShipLeadTime\Api\StockStateInterface
     */
    protected $shipLeadTimeStockState;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $courseModel;

    /**
     * Generate constructor.
     * @param Context $context
     * @param \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomer
     * @param \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkus
     * @param MachineConditionRuleFactory $machineConditionRule
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Customer\Model\CustomerRepository $customerRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Order\Email $helperSendEmail
     * @param \Riki\Subscription\Logger\LoggerOrder $loggerOrder
     * @param \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
     * @param \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     */
    public function __construct(
        Context $context,
        \Riki\SubscriptionMachine\Model\MachineCustomerFactory $machineCustomer,
        \Riki\SubscriptionMachine\Model\MachineSkusFactory $machineSkus,
        \Riki\SubscriptionMachine\Model\MachineConditionRuleFactory $machineConditionRule,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Customer\Model\CustomerRepository $customerRepository,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Order\Email $helperSendEmail,
        \Riki\Subscription\Logger\LoggerOrder $loggerOrder,
        \Riki\Subscription\Logger\LoggerFreeMachine $loggerFreeMachine,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory,
        \Riki\ShipLeadTime\Api\StockStateInterface $shipLeadTimeStockState,
        \Riki\SubscriptionCourse\Model\Course $courseModel
    ) {
        $this->machineCustomer = $machineCustomer;
        $this->machineSkus = $machineSkus;
        $this->machineConditionRule = $machineConditionRule;
        $this->courseFactory = $courseFactory;
        $this->membership =  $membership;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->rikiCustomerRepository = $customerRepository;
        $this->registry = $registry;
        $this->helperSendEmail = $helperSendEmail;
        $this->loggerOrder = $loggerOrder;
        $this->loggerFreeMachine = $loggerFreeMachine;
        $this->helperProfileData = $helperProfileData;
        $this->leadtimeFactory  = $leadtimeFactory;
        $this->shipLeadTimeStockState = $shipLeadTimeStockState;
        $this->courseModel = $courseModel;
        parent::__construct($context);
    }

    /**
     * Get logger for free machine flow
     *
     * @return \Riki\Subscription\Logger\LoggerFreeMachine
     */
    public function getFreeMachineLogger()
    {
        return $this->loggerFreeMachine;
    }

    /**
     * Validate free machine condition rule and add free machine to subscription order
     *
     * @param $quote
     * @param $customer
     * @param $courseId
     * @param $frequencyId
     * @return bool
     */
    public function addFreeMachineIntoOrderSubscription($quote, $customer, $courseId, $frequencyId)
    {
        /*reject out of stock order*/
        if ($quote->getData('is_oos_order')) {
            return false;
        }

        /*get subscription course data*/
        $courseModel = $this->courseFactory->create()->load($courseId);

        if (!$courseModel->getId()) {
            return false;
        }

        /*subscription course code*/
        $courseCode = $courseModel->getData('course_code');

        /*validate customer data*/
        if (!$customer->getCustomAttribute('consumer_db_id')) {
            return false;
        }

        $consumerDbId = $customer->getCustomAttribute('consumer_db_id')->getValue();

        /*profile id*/
        $profileId = $quote->getData('profile_id');

        /*log prefix for this profile*/
        $profileInfo = ($profileId) ? 'ProfileID '.$profileId.'::' : 'New profile::';

        if ($consumerDbId) {
            /*update machine_customer status from consumer before apply new free machine*/
            $this->updateMachineCustomerStatus($consumerDbId, $profileInfo);
        }

        /*Get all machine customer has status is one of (2,3,11,12,13) */
        $machineCustomer = $this->getMachineCustomerByConsumerDbId($consumerDbId);

        if (!$machineCustomer) {
            return false;
        }

        $this->quote = $quote;

        $quoteItems = $quote->getAllItems();

        if (!$quoteItems) {
            return false;
        }

        $quoteSubtotal = $quote->getSubtotal();

        /*add free machine to quote*/
        $this->addFreeMachineToQuote(
            $quote,
            $machineCustomer,
            $quoteItems,
            $quoteSubtotal,
            $courseCode,
            $frequencyId,
            $consumerDbId,
            $profileInfo,
            true
        );
    }

    /**
     * add free machine to specified quote
     *
     * @param $quote
     * @param $customer
     * @param $courseId
     * @param $frequencyId
     * @param $originalQuote (main quote of this profile)
     * @return bool
     */
    public function addFreeMachineToSpecifiedQuote(
        $quote,
        $customer,
        $courseId,
        $frequencyId,
        $originalQuote
    ) {
        /*reject out of stock order*/
        if ($quote->getData('is_oos_order')) {
            return false;
        }

        /*get subscription course data*/
        $courseModel = $this->courseFactory->create()->load($courseId);

        if (!$courseModel->getId()) {
            return false;
        }

        /*subscription course code*/
        $courseCode = $courseModel->getData('course_code');

        /*validate customer data*/
        if (!$customer->getCustomAttribute('consumer_db_id')) {
            return false;
        }

        $consumerDbId = $customer->getCustomAttribute('consumer_db_id')->getValue();

        /*profile id*/
        $profileId = $quote->getData('profile_id');

        /*log prefix for this profile*/
        $profileInfo = ($profileId) ? 'ProfileID '.$profileId.'::' : 'New profile::';

        if ($consumerDbId) {
            /*update machine_customer status from consumer before apply new free machine*/
            $this->updateMachineCustomerStatus($consumerDbId, $profileInfo);
        }

        /*Get all machine customer has status is one of (2,11,12,13) */
        $machineCustomer = $this->getMachineCustomerByConsumerDbId($consumerDbId);

        if (!$machineCustomer) {
            return false;
        }

        $this->quote = $quote;

        /**
         * quote items data from main profile/order
         * - will be used to validated to add free machine to new quote
         */
        $quoteItems = $originalQuote->getAllItems();

        if (!$quoteItems) {
            return false;
        }

        /**
         * subtotal from main profile/order/quote
         * - will be used to validated to add free machine to new quote
         */
        $quoteSubtotal = $originalQuote->getSubtotal();

        /*add free machine to quote*/
        $this->addFreeMachineToQuote(
            $quote,
            $machineCustomer,
            $quoteItems,
            $quoteSubtotal,
            $courseCode,
            $frequencyId,
            $consumerDbId,
            $profileInfo,
            false
        );
    }

    /**
     * Validate free machine condition rule before add to subscription profile
     *
     * @param $quoteItems
     * @param $quoteSubtotal
     * @param $condition
     * @return bool
     */
    protected function validateMachineConditionRule($quoteItems, $quoteSubtotal, $condition)
    {
        /*3. check category and product qty valid in conditions*/
        $validateCategory = $this->checkProductInCategoryIsMapCondition($quoteItems, $quoteSubtotal, $condition);

        if ($validateCategory) {
            return true;
        }

        return false;
    }

    /**
     * Get Machine Sku will be add to order
     *
     * @param string $machineTypeCode
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $skuSpecified
     * @param string $consumerDbId
     * @return mixed|bool
     */
    public function getMachineSkus($machineTypeCode, \Magento\Quote\Model\Quote $quote, $skuSpecified, $consumerDbId)
    {
        $oosSkus = [];
        $oosSku = $machineSkuAddedToCart = null;
        $customerSub = $this->rikiCustomerRepository->prepareInfoSubCustomer($consumerDbId);

        // Get machine skus for DUO machine
        if ($machineTypeCode == MachineConditionRule::MACHINE_CODE_DUO && $skuSpecified) {
            list($machineSkuAddedToCart, $oosSkus, $oosSku) = $this->getMachineSkuForDuoMachine(
                $machineTypeCode,
                $quote,
                $customerSub,
                $oosSkus,
                $oosSku
            );
        } else {
            list($machineSkuAddedToCart, $oosSkus, $oosSku) = $this->getMachineSkuForNormalMachine(
                $machineTypeCode,
                $quote,
                $customerSub,
                $oosSkus,
                $oosSku
            );
        }

        if ($machineSkuAddedToCart) {
            return $machineSkuAddedToCart;
        }

        $oosSku['sku_oos'] = implode(', ', $oosSkus);
        if (!$this->registry->registry('product_out_off_stock')) {
            $this->registry->register('product_out_off_stock', $oosSku);
        }
        return false;
    }

    /**
     * Check conditions to add free machine product to order;
     *
     * @param $quoteItems
     * @param $quoteSubtotal
     * @param $condition
     * @return bool
     */
    protected function checkProductInCategoryIsMapCondition($quoteItems, $quoteSubtotal, $condition)
    {
        $thresholdOfQuote = $quoteSubtotal;

        $categoryId = isset($condition['category_id']) ? $condition['category_id'] : null;
        $minQty = isset($condition['qty_min']) ? $condition['qty_min'] : null;
        $threshold = isset($condition['threshold']) ? $condition['threshold'] : null;

        $categoryModel = $this->categoryFactory->create()->load($categoryId);

        $categoryProducts = [];

        if ($categoryModel->getId()) {
            $categoryProducts = $categoryModel->getProductCollection()->getAllIds();
        }

        $quoteQty = 0;

        $isMapProduct = false;

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quoteItems as $item) {
            // Only validate for main product instead of all of products
            $buyRequest = $item->getBuyRequest();
            if ($item->getData('parent_item_id')
                || $item->getData('prize_id')
                || isset($buyRequest['options']['ampromo_rule_id'])
                || isset($buyRequest['options']['free_machine_item'])
            ) {
                continue;
            }

            if (in_array($item->getProductId(), $categoryProducts)) {
                $isMapProduct = true;
                if ($item->getQty() >= $minQty and $thresholdOfQuote >= $threshold) {
                    return true;
                } else {
                    $quoteQty += $item->getQty();
                }
            }
        }

        if ($isMapProduct and $quoteQty >= $minQty and $thresholdOfQuote >= $threshold) {
            return true;
        }

        return false;
    }

    /**
     * Check leadtime active
     *
     * @param $product
     * @return bool
     */
    public function checkLeadTimeActiveForMachine($product)
    {
        $deliveryType = $product->getCustomAttribute('delivery_type')->getValue();
        $regionId = $this->quote->getShippingAddress()->getRegionId();
        $prefecture = $this->helperProfileData->getPrefectureCodeOfRegion([$regionId]);
        $leadTimeModel = $this->leadtimeFactory->create()->getCollection()->addActiveToFilter();
        $leadTimeModel->addFieldToFilter('pref_id', $prefecture);
        $leadTimeModel->addFieldToFilter('delivery_type_code', $deliveryType);
        $leadTimeModel->addFieldToFilter('is_active', 1);
        $leadTimeModel->setOrder('shipping_lead_time', 'DESC');
        return $leadTimeModel;
    }

    /**
     * is ambassador customer
     *
     * @param $customer
     * @return bool
     */
    public function isAmbassadorCustomer($customer)
    {
        if ($customer && $customer->getId()) {
            $customerMembership = $customer->getCustomAttribute('membership');
            if ($customerMembership) {
                $arrayOfCustomerMembership =  explode(',', $customerMembership->getValue());
                if (in_array(\Riki\Customer\Model\Customer::MEMBERSHIP_AMB, $arrayOfCustomerMembership)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * update machine_customer status from consumer before apply new free machine
     *
     * @param $consumerDbId
     * @param $profileInfo
     */
    protected function updateMachineCustomerStatus($consumerDbId, $profileInfo)
    {
        $customerSub = $this->rikiCustomerRepository->prepareInfoSubCustomer($consumerDbId);

        if ($customerSub) {
            $customerStatus = [];
            if (isset($customerSub['LENDING_STATUS_NBA'])) {
                $customerStatus['NBA'] = $customerSub['LENDING_STATUS_NBA'];
            }
            if (isset($customerSub['LENDING_STATUS_NDG'])) {
                $customerStatus['NDG'] = $customerSub['LENDING_STATUS_NDG'];
            }
            if (isset($customerSub['LENDING_STATUS_SPT'])) {
                $customerStatus['SPT'] = $customerSub['LENDING_STATUS_SPT'];
            }
            if (isset($customerSub['LENDING_STATUS_ICS'])) {
                $customerStatus['BLC'] = $customerSub['LENDING_STATUS_ICS'];
            }
            if (isset($customerSub['LENDING_STATUS_NSP'])) {
                $customerStatus['Nespresso'] = $customerSub['LENDING_STATUS_NSP'];
            }
            if (isset($customerSub['LENDING_STATUS_DUO'])) {
                $customerStatus['DUO'] = $customerSub['LENDING_STATUS_DUO'];
            }
            foreach ($customerStatus as $machineTypeCodeSub => $customerStatusSub) {
                $machineCustomerModel =  $this->machineCustomer->create()->getCollection();
                $machineCustomerModel->addFieldToFilter('consumer_db_id', $consumerDbId);
                $machineCustomerModel->addFieldToFilter('machine_type_code', $machineTypeCodeSub);
                $machineCustomerModel->setPageSize(1);
                $machineCustomerSub = $machineCustomerModel->getFirstItem();
                if ($machineCustomerSub->getId()) {
                    $oldMachineTypeCodeStatus = $machineCustomerSub->getData('status');
                    if ($oldMachineTypeCodeStatus != $customerStatusSub) {
                        $machineCustomerSub->setData('status', $customerStatusSub);
                        try {
                            $machineCustomerSub->save();

                            $this->loggerFreeMachine->addInfo(
                                $profileInfo .
                                '::Customer #' .
                                $consumerDbId .
                                '::' .
                                $machineTypeCodeSub .
                                '::Status changed from ' .
                                $oldMachineTypeCodeStatus .
                                ' to ' .
                                $customerStatusSub
                            );
                        } catch (\Exception $e) {
                            $this->loggerFreeMachine->addError(
                                $profileInfo .
                                'Customer #' .
                                $consumerDbId .
                                '::' .
                                $machineTypeCodeSub .
                                '::Status could not update to ' .
                                $customerStatusSub
                            );

                            $this->loggerFreeMachine->addCritical($e);
                        }
                    } else {
                        $this->loggerFreeMachine->addInfo(
                            $profileInfo .
                            'Customer #' .
                            $consumerDbId .
                            '::' .
                            $machineTypeCodeSub .
                            '::Status ' .
                            $customerStatusSub .
                            ' nothing to update'
                        );
                    }
                } else {
                    $machineCustomerModelCreate = $this->machineCustomer->create();
                    $data = [
                        'machine_type_code' => $machineTypeCodeSub,
                        'consumer_db_id' => $consumerDbId,
                        'sku' => null,
                        'status' => $customerStatusSub
                    ];
                    $machineCustomerModelCreate->setData($data);
                    try {
                        $machineCustomerModelCreate->save();

                        $this->loggerFreeMachine->addInfo(
                            $profileInfo .
                            'Customer #' .
                            $consumerDbId .
                            '::MachineTypeCode' .
                            $machineTypeCodeSub .
                            ' with status' .
                            $customerStatusSub .
                            ' created'
                        );
                    } catch (\Exception $e) {
                        $this->loggerFreeMachine->addError(
                            $profileInfo .
                            'Customer #' .
                            $consumerDbId .
                            '::MachineTypeCode' .
                            $machineTypeCodeSub .
                            ' with status ' .
                            $customerStatusSub .
                            ' could not create'
                        );

                        $this->loggerFreeMachine->addCritical($e);
                    }
                }
            }
        }
    }

    /**
     * get Machine Customer By Consumer Db Id
     *
     * @param $consumerDbId
     * @return bool|\Riki\SubscriptionMachine\Model\ResourceModel\MachineCustomer\Collection
     */
    protected function getMachineCustomerByConsumerDbId($consumerDbId)
    {
        /** @var \Riki\SubscriptionMachine\Model\ResourceModel\MachineCustomer\Collection $machineCustomer */
        $machineCustomer = $this->machineCustomer->create()->getCollection();
        $machineCustomer->addFieldToFilter('consumer_db_id', $consumerDbId);
        $machineCustomer->addFieldToFilter(
            'status',
            ['in' =>
                [
                    StatusMachine::MACHINE_STATUS_VALUE_REQUESTED,
                    StatusMachine::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE,
                    StatusMachine::MACHINE_STATUS_VALUE_OOS,
                    StatusMachine::MACHINE_STATUS_VALUE_NOT_APPLICABLE_PRODUCT_PURCHASED,
                    StatusMachine::MACHINE_STATUS_VALUE_NOT_PURCHASED
                ]
            ]
        );

        if ($machineCustomer->getSize()) {
            return $machineCustomer;
        }

        return false;
    }

    /**
     * Add free machine to quote
     *
     * @param $quote
     * @param $machineCustomer
     * @param $quoteItems
     * @param $quoteSubtotal
     * @param $courseCode
     * @param $frequencyId
     * @param $consumerDbId
     * @param $profileInfo
     * @param $quoteCollectTotalsAfterAddItem //quote need to call collect total again after add free machine
     */
    protected function addFreeMachineToQuote(
        $quote,
        $machineCustomer,
        $quoteItems,
        $quoteSubtotal,
        $courseCode,
        $frequencyId,
        $consumerDbId,
        $profileInfo,
        $quoteCollectTotalsAfterAddItem
    ) {
        $productAddedToCart = [];
        foreach ($machineCustomer as $machine) {
            $machineTypeCode = $machine->getData('machine_type_code');
            if ($machineTypeCode) {
                /* 1. Check the subscription course and frequency */
                /** @var $machineConditionRuleModel MachineConditionRuleCollection */
                $machineConditionRuleModel = $this->machineConditionRule->create()->getCollection();
                $machineConditionRuleModel->addFieldToFilter('machine_code', $machineTypeCode);/* list A*/
                foreach ($machineConditionRuleModel as $ruleId => $machineRule) {
                    $machineConditionCourse = json_decode($machineRule->getData('course_code'));
                    $machineConditionFrequency = json_decode($machineRule->getData('frequency'));
                    if (!(in_array($courseCode, $machineConditionCourse) &&
                        in_array($frequencyId, $machineConditionFrequency))
                    ) {
                        $machineConditionRuleModel->removeItemByKey($ruleId);
                    }

                    /* Check the payment method if machine condition payment method is not null */
                    if ($machineRule->getData('payment_method')) {
                        $machineConditionPaymentMethod = $this->getAllowMachineConditionPaymentMethod(
                            json_decode($machineRule->getData('payment_method'))
                        );
                        if (!(in_array($quote->getPayment()->getMethod(), $machineConditionPaymentMethod))) {
                            $machineConditionRuleModel->removeItemByKey($ruleId);
                        }
                    }
                }
                /*after foreach then get List B -> go to check 2*/
                /*2. Check if the condition apply for the free machine*/
                if (sizeof($machineConditionRuleModel->getItems()) > 0) {
                    foreach ($machineConditionRuleModel as $ruleId => $machineRule) {
                        $condition = [];
                        $condition['category_id'] = $machineRule->getData('category_id');
                        $condition['qty_min'] = $machineRule->getData('qty_min');
                        $condition['threshold'] = $machineRule->getData('threshold');
                        $validate = $this->validateMachineConditionRule($quoteItems, $quoteSubtotal, $condition);
                        if (!$validate) {
                            $machineConditionRuleModel->removeItemByKey($ruleId);
                        }
                    }
                    /* after foreach then get List C -> go to check 3*/
                    /* 3. Check if the SKU is in stock*/
                    if (sizeof($machineConditionRuleModel->getItems()) > 0) {
                        /*get WBS of first condition*/
                        $firstMachineConditionRule = $machineConditionRuleModel->getFirstItem();
                        $wbs = $firstMachineConditionRule->getData('wbs');
                        /*get Machine SKUs*/
                        $product = $this->getMachineSkus(
                            $machine->getData('machine_type_code'),
                            $quote,
                            $firstMachineConditionRule->getData('sku_specified'),
                            $consumerDbId
                        );
                        if (!$product) {
                            /*Status 11*/
                            $productAddedToCart[$machineTypeCode] = [
                                'machine' => $machine,
                                'consumer_db_id' => $consumerDbId,
                                'status' => 11,
                                'type' => 'oos'
                            ];

                            if ($this->registry->registry('product_out_off_stock')) {
                                $oosSku = $this->registry->registry('product_out_off_stock');
                                $oosSku['machine_type_code'] = $machineTypeCode;
                                $productAddedToCart[$machineTypeCode]['variables'] = $oosSku;
                                $productAddedToCart[$machineTypeCode]['sku'] = (isset($oosSku['sku']))
                                    ? $oosSku['sku'] : '';
                                $product = (isset($oosSku['product'])) ? $oosSku['product'] : '';
                                $productAddedToCart[$machineTypeCode]['name'] = ($product)
                                    ? $product->getName() : '';
                                // Change machine rental status to “1. In rental” in “CUSTOMER_SUB”
                                // If this is generate order with OOS duo machine
                                if (isset($oosSku['is_duo_machine']) && $oosSku['is_duo_machine']) {
                                    $productAddedToCart[$machineTypeCode]['status'] = 1;
                                }
                                $this->loggerFreeMachine->addInfo(
                                    $profileInfo .
                                    'Customer #' .
                                    $consumerDbId .
                                    '::' .
                                    $machineTypeCode .
                                    ' with SKU ' .
                                    (isset($oosSku['sku']) ? $oosSku['sku'] .' OOS' : 'not defined')
                                );
                            }

                            // out of stock situation which handled by AdvancedInventory module
                            $oosData = $this->registry->registry('product_out_off_stock') ?: [];
                            $this->_eventManager->dispatch(
                                \Riki\AdvancedInventory\Observer\OosCapture::EVENT,
                                [
                                    'quote' => $quote,
                                    'machine_sku' => isset($oosData['machine_sku']) ? $oosData['machine_sku'] : null,
                                    'product' => isset($oosData['product']) ? $oosData['product'] : null,
                                    'is_duo_machine' => (isset($oosData['is_duo_machine']) &&
                                        $oosData['is_duo_machine']) ? 1 : 0,
                                    'is_sku_specified' => (isset($oosData['is_sku_specified']) &&
                                        $oosData['is_sku_specified']) ? 1 : 0,
                                    'machine_data' => $productAddedToCart[$machineTypeCode]
                                ]
                            );

                            $this->registry->unregister('product_out_off_stock');
                        } else {
                            if ($product instanceof \Magento\Catalog\Model\Product) {
                                /*3. Add free machine product to Quote*/
                                /*Status 1*/
                                $requestInfo = [
                                    'qty' => $this->getRequestedQuantity($product),
                                    'options' => [
                                        'free_machine_item' => 1
                                    ]
                                ];

                                $quoteItem = $quote->addProduct(
                                    $product,
                                    new \Magento\Framework\DataObject($requestInfo)
                                );

                                if (is_string($quoteItem)) {
                                    $this->loggerOrder->info(
                                        'Free Machine Product ' .
                                        $product->getName() .
                                        ' cannot add to cart'
                                    );

                                    $this->loggerFreeMachine->addInfo(
                                        $profileInfo .
                                        'Customer #' .
                                        $consumerDbId .
                                        '::' .
                                        $machineTypeCode.
                                        ' cannot add free machine ' .
                                        $product->getName()
                                    );

                                    $this->loggerOrder->critical($quoteItem);
                                    return false;
                                }

                                $this->loggerOrder->info(
                                    'Free Machine Product ' .
                                    $product->getName() .
                                    ' has been added to the cart'
                                );

                                $this->loggerFreeMachine->addInfo(
                                    $profileInfo .
                                    'Customer #' .
                                    $consumerDbId .
                                    '::' .
                                    $machineTypeCode .
                                    ' added free machine ' .
                                    $product->getName()
                                );

                                // Add is_duo_machine to addition data for quote item
                                // It will use for check to update status 'Pending_for_machine'
                                if ($product->getData('is_duo_machine')) {
                                    $additionalData = json_decode(
                                        $quoteItem->getData('additional_data') ?: '{}',
                                        true
                                    );
                                    $additionalData['is_duo_machine'] = $product->getData('is_duo_machine');
                                    $quoteItem->setData('additional_data', json_encode($additionalData));
                                }

                                $quoteItem->setData('visible_user_account', true);
                                $quoteItem->setData('is_riki_machine', true);
                                $quoteItem->setData('foc_wbs', $wbs);
                                $quote->save();
                                $productAddedToCart[$machineTypeCode] = [
                                    'machine' => $machine,
                                    'consumer_db_id' => $consumerDbId,
                                    'status' => 1,
                                    'quote_item_id' => $quoteItem->getId(),
                                    'name' => $quoteItem->getName(),
                                    'sku' => $product->getSku()
                                ];
                            } else {
                                $productSku = isset($product['sku'])?$product['sku']:null;
                                $this->loggerFreeMachine->addError(
                                    $profileInfo .
                                    ' cannot attached free machine [SKU] because ' .
                                    $productSku .
                                    ' was inactive with warehouse/delivery type/prefecture.'
                                );
                            }
                        }
                    } else {
                        /*Status 12*/
                        $this->loggerFreeMachine->addInfo(
                            $profileInfo .
                            'Customer #' .
                            $consumerDbId .
                            '::' .
                            $machineTypeCode .
                            ' failed to add free machine (ERROR 12)'
                        );

                        $productAddedToCart[$machineTypeCode] = [
                            'machine' => $machine,
                            'consumer_db_id' => $consumerDbId,
                            'status' => 12,
                            'type' => 'ambassador'
                        ];
                    }
                } else {
                    /*Status 13*/
                    $this->loggerFreeMachine->addInfo(
                        $profileInfo .
                        'Customer #' .
                        $consumerDbId .
                        '::' .
                        $machineTypeCode .
                        ' failed to add free machine (ERROR 13)'
                    );

                    $productAddedToCart[$machineTypeCode] = [
                        'machine' => $machine,
                        'consumer_db_id' => $consumerDbId,
                        'status' => 13,
                        'type' => 'ambassador_sub'
                    ];
                }
            }
        }

        /**
         * flag to check this is free machine quote (created for free machine order)
         *     free machine order is a separately order
         *     which was created with only free machine item
         */
        $isFreeMachineOrder = $quote->getData('free_machine_order');

        /**
         *  generate a registry to update customer status
         *      - event checkout submit all after
         */
        if ($isFreeMachineOrder) {
            $this->registry->unregister('free_machine_added_to_free_machine_cart');
            $this->registry->register('free_machine_added_to_free_machine_cart', $productAddedToCart);
        } else {
            $this->registry->unregister('free_machine_added_to_cart');
            $this->registry->register('free_machine_added_to_cart', $productAddedToCart);
        }
    }

    /**
     * get requested quantity will be add to cart for free machine
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    protected function getRequestedQuantity($product)
    {
        /*default quantity will be add to cart*/
        $requestedQty = 1;

        $productUnitQty = 1;

        if ($product->getCaseDisplay() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $productUnitQty = (int)$product->getUnitQty() ? (int)$product->getUnitQty() : 1;
        }

        return $requestedQty * $productUnitQty;
    }

    /**
     * Get allow machine condition payment method
     *
     * @param array $allowPaymentMethods
     * @return array
     */
    protected function getAllowMachineConditionPaymentMethod($allowPaymentMethods)
    {
        $listPayments = [];
        if ($allowPaymentMethods) {
            foreach ($allowPaymentMethods as $id) {
                $listPayments[] = $this->courseModel->mapPaymentMethod($id);
            }
        }

        return $listPayments;
    }

    /**
     * Get Machine Sku for Duo machine will be add to order
     *
     * @param string $machineTypeCode
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $customerSub
     * @param array $oosSkus
     * @param array $oosSku
     *
     * @return array
     */
    private function getMachineSkuForDuoMachine(
        $machineTypeCode,
        \Magento\Quote\Model\Quote $quote,
        $customerSub,
        $oosSkus,
        $oosSku
    ) {
        $machineSkuAddedToCart = null;

        // The system will get Specified SKU from API with code "AMB_DUO_SKU" (DUO Machine)
        // If Specified SKU is in stock then add this machine SKU to the subscription order
        $ambDuoSku = isset($customerSub['AMB_DUO_SKU']) ? $customerSub['AMB_DUO_SKU'] : '';
        if ($ambDuoSku) {
            try {
                $productModel = $this->productRepository->get($ambDuoSku);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Write log if specified sku from API doesn't exist in magento and continue to do the next step
                $productModel = null;
                $this->loggerOrder->info('Product specified SKU #' . $ambDuoSku . ' doesn\'t exist');
            }

            if ($productModel && $productModel->getStatus() == 1) {
                $unitQty = 1;
                if ($productModel->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                    $unitQty = (int)$productModel->getUnitQty() ? $productModel->getUnitQty() : 1;
                }

                $requestedQty = $unitQty;

                $availableQty = $this->shipLeadTimeStockState->checkAvailableQty(
                    $quote,
                    $productModel->getSku(),
                    $requestedQty
                );

                if ($availableQty >= $requestedQty) {
                    /**
                     * Check allow spot order.  Allow Spot order = No => Products are OOS
                     * @param $productModel
                     * @return bool
                     */
                    $allowSpotOrder = $productModel->getData('allow_spot_order');

                    /*quote is generated automatically by subscription flow*/
                    $isGenerateQuote = $quote->getData(
                        \Riki\Subscription\Helper\Order\Data::IS_PROFILE_GENERATED_ORDER_KEY
                    );

                    if ($allowSpotOrder || $isGenerateQuote) {
                        $productModel->addCustomOption('is_free_machine', 1);
                        if (isset($customerSub['LENDING_STATUS_DUO']) &&
                            $customerSub['LENDING_STATUS_DUO'] ==
                            StatusMachine::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE
                        ) {
                            $productModel->setData('is_duo_machine', 1);
                        }
                        $machineSkuAddedToCart = $productModel;
                        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
                    }
                } else {
                    if (sizeof($this->checkLeadTimeActiveForMachine($productModel)) == 0) {
                        $machineSkuAddedToCart = [
                            'sku' => $productModel->getSku()
                        ];
                        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
                    }
                }

                // Add AMB_SKU_DUO to oos table
                $oosSkus[] = $productModel->getSku();

                $oosSku['product_id'] = $productModel->getId();
                $oosSku['sku'] = $productModel->getSku();
                $oosSku['type_id'] = $productModel->getTypeId();
                $oosSku['machine_sku'] = '';
                $oosSku['product'] = $productModel;
                $oosSku['is_sku_specified'] = 1;
                if (isset($customerSub['LENDING_STATUS_DUO']) &&
                    $customerSub['LENDING_STATUS_DUO'] == StatusMachine::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE
                ) {
                    $oosSku['is_duo_machine'] = 1;
                }
            }
        }

        // If there is not any Specified SKU found from API
        // The system gets the list of Machine SKUs in MACHINE_SKUS for the related machine type sort by priority
        list($machineSkuAddedToCart, $oosSkus, $oosSku) = $this->getMachineSkuForNormalMachine(
            $machineTypeCode,
            $quote,
            $customerSub,
            $oosSkus,
            $oosSku
        );

        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
    }

    /**
     * Get Machine Sku for normal machine will be add to order
     *
     * @param string $machineTypeCode
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $customerSub
     * @param array $oosSkus
     * @param array $oosSku
     *
     * @return array
     */
    private function getMachineSkuForNormalMachine(
        $machineTypeCode,
        \Magento\Quote\Model\Quote $quote,
        $customerSub,
        $oosSkus,
        $oosSku
    ) {
        $machineSkuAddedToCart = null;

        // Gets the list of Machine SKUs in MACHINE_SKUS for the related machine type sort by priority
        $machineSkusModel = $this->machineSkus->create()->getCollection();
        $machineSkusModel->addFieldToFilter('machine_type_code', $machineTypeCode);
        $machineSkusModel->addOrder('priority', 'ASC');
        foreach ($machineSkusModel as $item) {
            $productModel = $this->productRepository->get($item->getData('sku'));
            if ($productModel->getStatus() == 1) {
                $requestedQty = max($item['qty'], 1);

                $unitQty = 1;
                if ($productModel->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                    $unitQty = (int)$productModel->getUnitQty() ? $productModel->getUnitQty() : 1;
                }

                $requestedQty = $requestedQty * $unitQty;

                $availableQty = $this->shipLeadTimeStockState->checkAvailableQty(
                    $quote,
                    $productModel->getSku(),
                    $requestedQty
                );

                if ($availableQty >= $requestedQty) {
                    /**
                     * Check allow spot order.  Allow Spot order = No => Products are OOS
                     * @param $productModel
                     * @return bool
                     */
                    $allowSpotOrder = $productModel->getData('allow_spot_order');

                    /*quote is generated automatically by subscription flow*/
                    $isGenerateQuote = $quote->getData(
                        \Riki\Subscription\Helper\Order\Data::IS_PROFILE_GENERATED_ORDER_KEY
                    );

                    if ($allowSpotOrder || $isGenerateQuote) {
                        $productModel->addCustomOption('is_free_machine', 1);
                        $productModel->setData('machine_wbs', $item->getData('wbs'));
                        if ($machineTypeCode == MachineConditionRule::MACHINE_CODE_DUO &&
                            isset($customerSub['LENDING_STATUS_DUO']) &&
                            $customerSub['LENDING_STATUS_DUO'] ==
                            StatusMachine::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE
                        ) {
                            $productModel->setData('is_duo_machine', 1);
                        }
                        $machineSkuAddedToCart = $productModel;
                        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
                    }
                } else {
                    if (sizeof($this->checkLeadTimeActiveForMachine($productModel)) == 0) {
                        $machineSkuAddedToCart = [
                            'sku' => $productModel->getSku()
                        ];
                        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
                    }
                }

                $oosSkus[] = $productModel->getSku();

                if ($oosSku === null) {
                    $oosSku['product_id'] = $productModel->getId();
                    $oosSku['sku'] = $productModel->getSku();
                    $oosSku['type_id'] = $productModel->getTypeId();
                    $oosSku['machine_sku'] = $item;
                    $oosSku['product'] = $productModel;

                    if ($machineTypeCode == MachineConditionRule::MACHINE_CODE_DUO &&
                        isset($customerSub['LENDING_STATUS_DUO']) &&
                        $customerSub['LENDING_STATUS_DUO'] == StatusMachine::MACHINE_STATUS_VALUE_PENDING_FOR_MACHINE
                    ) {
                        $oosSku['is_duo_machine'] = 1;
                    }
                }
            }
        }

        return [$machineSkuAddedToCart, $oosSkus, $oosSku];
    }
}
