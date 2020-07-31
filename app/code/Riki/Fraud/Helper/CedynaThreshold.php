<?php

namespace Riki\Fraud\Helper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Search\Grid\Renderer\Price;

class CedynaThreshold extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CEDYNA_COUNTER_ATTRIBUTE = 'cedyna_counter';
    const SHOSHA_CODE_ATTRIBUTE = 'shosha_business_code';
    const FROM_WEB_ORDER = 'Web Order';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteria;
    /**
     * @var \Riki\Fraud\Model\OrderCedynaThresholdFactory
     */
    protected $_orderCedynaThreshold;
    /**
     * @var \Riki\Fraud\Model\RmaCedynaThresholdFactory
     */
    protected $_rmaCedynaThreshold;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $_shoshaHelper;
    /**
     * @var PriceHelper
     */
    protected $_priceHelper;

    /**
     * @var \Riki\Rma\Api\RmaManagementInterface
     */
    protected $_rmaManagement;

    /**
     * CedynaThreshold constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Fraud\Model\OrderCedynaThresholdFactory $orderCedynaThresholdFactory
     * @param \Riki\Fraud\Model\RmaCedynaThresholdFactory $rmaCedynaThresholdFactory
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     * @param PriceHelper $priceHelper
     * @param \Riki\Rma\Api\RmaManagementInterface $rmaManagement
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Fraud\Model\OrderCedynaThresholdFactory $orderCedynaThresholdFactory,
        \Riki\Fraud\Model\RmaCedynaThresholdFactory $rmaCedynaThresholdFactory,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper,
        PriceHelper $priceHelper,
        \Riki\Rma\Api\RmaManagementInterface $rmaManagement
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_dateTime = $dateTime;
        $this->_customerRepository = $customerRepository;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $searchCriteriaBuilder;
        $this->_orderCedynaThreshold = $orderCedynaThresholdFactory;
        $this->_rmaCedynaThreshold = $rmaCedynaThresholdFactory;
        $this->_shoshaHelper = $shoshaHelper;
        $this->_priceHelper = $priceHelper;
        $this->_rmaManagement = $rmaManagement;
    }

    /**
     * check order is threshold exceed
     *
     * @param $order
     * @return boolean
     */
    public function isThresholdExceed($order)
    {
        /*only process for invoice order*/
        $payment = $order->getPayment();
        if (empty($payment)
            || $payment->getMethod() != \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED) {
            return false;
        }

        if (!$order->getCustomerId()) {
            return false;
        }

        /*get customer data*/
        $customer = $this->getCustomerById($order->getCustomerId());
        if (!$customer) {
            return false;
        }

        /*get business data of order customer*/
        $business = $this->getBusinessData($customer);
        if (!$business) {
            return false;
        }

        /*this business is Cedyna - only counter for cedyna business*/
        $isCedyna = $this->_shoshaHelper->isCedynaBusinessByData($business);
        if (!$isCedyna) {
            return false;
        }

        /*check this order is tracked or not yet */
        $isProcess = $this->isProcessOrder($order->getId());
        if ($isProcess) {
            return false;
        }

        /*current cedyna counter for this business*/
        $currentCedynaCounter = $business->getCedynaCounter();

        /*new cedyna counter for this business*/
        $newCedynaCounter = $currentCedynaCounter + $order->getGrandTotal();

        try{
            /*increase Cedyna counter*/
            $this->updateCedynaCounter($business, $newCedynaCounter);

            $this->trackingOrder($order, $business->getId());

            if ($newCedynaCounter > $this->getCedynaThreshold()) {
                if (empty($order->getCreatedBy()) || $order->getCreatedBy() == self::FROM_WEB_ORDER) {
                    $this->sendEmailNotification($newCedynaCounter,$business->getData(self::SHOSHA_CODE_ATTRIBUTE));
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * update cedyna monthly counter for business
     *
     * @param $business
     * @param $newCedynaCounter
     * @return bool
     */
    public function updateCedynaCounter($business, $newCedynaCounter)
    {
        try {
            $business->setData(self::CEDYNA_COUNTER_ATTRIBUTE, $newCedynaCounter);
            $business->save();
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Decrease Cedyna counter value for business if order cancelled
     *
     * @param $orderId
     * @return bool
     */
    public function updateCedynaValueAfterCancelOrder($orderId)
    {
        $trackedOrder = $this->getOrderToReturnCedynaValue($orderId);

        if ($trackedOrder) {
            $business = $this->_shoshaHelper->getBusinessDataById($trackedOrder->getShoshaId());
            if ($business) {

                $currentCedynaValue = $business->getData(self::CEDYNA_COUNTER_ATTRIBUTE);
                $returnCedynaValue = $trackedOrder->getData('order_cedyna_value');
                $newCedynaValue = $currentCedynaValue - $returnCedynaValue;

                if ($newCedynaValue < 0) {
                    $newCedynaValue = 0;
                }

                try {
                    /*update new cedyna counter for this business, descrease*/
                    $this->updateCedynaCounter($business, $newCedynaValue);

                    /*change flag to know this order is return all cedyna value*/
                    $trackedOrder->setData('is_cancelled', 1);
                    $trackedOrder->save();
                } catch (\Exception $e) {
                    $this->_logger->critical($e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Decrease Cedyna value after return approved
     *
     * @param $rma
     * @return bool|void
     */
    public function updateCedynaValueAfterReturnApproved($rma)
    {
        if (empty($rma) || !$rma->getId()) {
            return false;
        }

        $rmaCedynaThreshold = $this->getRmaCedynaThresholdData($rma);

        if ($rmaCedynaThreshold) {
            return $this->returnCedynaValueForRma($rmaCedynaThreshold);
        }

        return false;

    }

    /**
     * Return cedyna value for rma
     *
     * @param $rmaCedynaThreshold
     * @return bool
     */
    public function returnCedynaValueForRma($rmaCedynaThreshold)
    {
        $orderThresHold = $this->getOrderToReturnCedynaValue($rmaCedynaThreshold->getOrderId());

        if ($orderThresHold) {

            $business = $this->_shoshaHelper->getBusinessDataById($orderThresHold->getShoshaId());

            if($business) {

                $currentCedynaValue = $business->getData(self::CEDYNA_COUNTER_ATTRIBUTE);
                $newReturnCedynaValue = $rmaCedynaThreshold->getRmaCedynaValue();
                $newCedynaValue = $currentCedynaValue - $newReturnCedynaValue;

                if ($newCedynaValue < 0) {
                    $newCedynaValue = 0;
                }

                return $this->updateCedynaCounter($business, $newCedynaValue);
            }
        }

        return false;
    }


    /**
     * Get tracking threshold data for this rma
     *
     * @param $rma
     * @return bool|\Magento\Framework\DataObject|mixed
     */
    public function getRmaCedynaThresholdData($rma)
    {
        $isProcessOrder = $this->isProcessOrder($rma->getOrderId());

        if ($isProcessOrder) {

            $rmaThresholdItem = $this->isProcessRma($rma->getId());

            if ($rmaThresholdItem) {

                /* set new return amount for tracking data */
                $cedynaValue = $this->_rmaManagement->getReturnedGoodsAmount($rma) + floatval($rma->getData('return_shipping_fee')) + floatval($rma->getData('total_return_point_adjusted'));
                $rmaThresholdItem->setRmaCedynaValue($cedynaValue);

                try {
                    $rmaThresholdItem->save();
                } catch (\Exception $e) {
                    $this->_logger->critical($e->getMessage());
                }

            } else {
                $rmaThresholdItem = $this->trackingRma($rma);
            }

            return $rmaThresholdItem;
        }

        return false;
    }


    /**
     * @param $orderId
     * @return bool
     */
    public function isProcessOrder($orderId)
    {
        $factory = $this->_orderCedynaThreshold->create();
        $collection = $factory->getCollection();
        $collection->addFieldToFilter('order_id', $orderId);
        if ($collection->getSize()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $rmaId
     * @return bool|\Magento\Framework\DataObject
     */
    public function isProcessRma($rmaId)
    {
        $factory = $this->_rmaCedynaThreshold->create();
        /** @var \Riki\Fraud\Model\ResourceModel\RmaCedynaThreshold\Collection $collection */
        $collection = $factory->getCollection();
        $collection->addFieldToFilter('rma_id', $rmaId);
        if ($collection->getSize()) {
            return $collection->setPageSize(1)->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * get business data by customer object
     *
     * @param $customer
     * @return bool|\Magento\Framework\DataObject
     */
    public function getBusinessData($customer)
    {
        return $this->_shoshaHelper->getBusinessDataByCustomer($customer);
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function isCedynaCustomerById($customerId)
    {
        return $this->_shoshaHelper->isCedynaCustomer($customerId);
    }

    /**
     * @param $customer
     * @return bool
     */
    public function isCedynaCustomer($customer)
    {
        return $this->_shoshaHelper->isCedynaCustomerByData($customer);
    }

    /**
     * will exceed after place order success ( condition for place order BO )
     *
     * @param $customerId
     * @param $nextValue
     * @return bool
     */
    public function willCedynaThresholdExceed($customerId, $nextValue)
    {
        $customer = $this->getCustomerById($customerId);

        if (!$customer) {
            return false;
        }

        /*get business data of order customer*/
        $business = $this->getBusinessData($customer);
        if (!$business) {
            return false;
        }

        /*this business is Cedyna - only counter for cedyna business*/
        $isCedyna = $this->_shoshaHelper->isCedynaBusinessByData($business);
        if (!$isCedyna) {
            return false;
        }

        /*current cedyna counter for this business*/
        $currentCedynaCounter = $business->getCedynaCounter();

        /*new cedyna counter for this business*/
        $newCedynaCounter = $currentCedynaCounter + $nextValue;

        if ($newCedynaCounter > $this->getCedynaThreshold()) {
            return true;
        }

        return false;
    }

    /**
     * Add a record to tracking threshold value for this order
     *
     * @param $order
     * @param $shoshaId
     */
    public function trackingOrder($order, $shoshaId)
    {
        $tracking = $this->_orderCedynaThreshold->create();
        $tracking->setOrderId($order->getId());
        $tracking->setOrderIncrementId($order->getIncrementId());
        $tracking->setOrderCreatedFrom($order->getCreatedBy());
        $tracking->setCustomerId($order->getCustomerId());
        $tracking->setShoshaId($shoshaId);
        $tracking->setOrderCedynaValue($order->getGrandTotal());
        $tracking->setMonth($this->_dateTime->date('m'));
        $tracking->setYear($this->_dateTime->date('Y'));
        try{
            $tracking->save();
        }catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
        }
    }

    /**
     * Add a record to tracking threshold value for this rma
     *
     * @param $rma
     * @return mixed
     */
    public function trackingRma($rma)
    {
        $tracking = $this->_rmaCedynaThreshold->create();
        $tracking->setRmaId($rma->getId());
        $tracking->setRmaIncrementId($rma->getIncrementId());
        $tracking->setOrderId($rma->getOrderId());
        $tracking->setOrderIncrementId($rma->getOrderIncrementId());
        $tracking->setCustomerId($rma->getCustomerId());
        $cedynaValue = $this->_rmaManagement->getReturnedGoodsAmount($rma) + floatval($rma->getData('return_shipping_fee')) + floatval($rma->getData('total_return_point_adjusted'));
        $tracking->setRmaCedynaValue($cedynaValue);
        try{
            $tracking->save();
            return $tracking;
        }catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
        }
    }

    /**
     * @param $cedynaCounter
     * @param null $businessCode
     */
    public function sendEmailNotification($cedynaCounter, $businessCode = null)
    {
        try {
            /* controlled by Email Marketing */
            /* Email: Cedyna threshold exceed notification */
            $this->_inlineTranslation->suspend();
            $this->generateTemplate($cedynaCounter,$businessCode);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
    }

    /**
     * @param $cedynaCounter
     * @param null $businessCode
     * @return $this
     */
    public function generateTemplate($cedynaCounter,$businessCode = null)
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier( $this->getEmailTemplate() )
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars( [
                'year' => $this->_dateTime->date('Y'),
                'month' => $this->_dateTime->date('m'),
                'cedyna_threshold' => $this->_priceHelper->currency($this->getCedynaThreshold(),true,false),
                'business_code' => $businessCode,
                'current_cedyna_value' => $this->_priceHelper->currency($cedynaCounter,true,false)
            ] )
            ->setFrom($senderInfo)
            ->addTo( explode( ';', $this->getReceivedEmailAddress() ) );
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCedynaThreshold()
    {
        return $this->_scopeConfig->getValue('rikifraud/general/cedyna_threshold');
    }


    /**
     * @return string
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->_scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->_scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->_scopeConfig->getValue('rikifraud/email/cedyna_threshold_notification');
    }

    /**
     * @return string
     */
    public function getReceivedEmailAddress()
    {
        return $this->_scopeConfig->getValue('rikifraud/email/received_email');
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function getOrderById($orderId)
    {
        $criteria = $this->_searchCriteria->addFilter('entity_id', $orderId )
            ->create();

        $orderCollection = $this->_orderRepository->getList($criteria);

        if ($orderCollection->getTotalCount()) {
            return $orderCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $customerId
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomerById($customerId)
    {
        try {
            return $this->_customerRepository->getById($customerId);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * @param $orderId
     * @return bool|\Magento\Framework\DataObject
     */
    public function getOrderToReturnCedynaValue($orderId)
    {
        $factory = $this->_orderCedynaThreshold->create();
        /** @var \Riki\Fraud\Model\ResourceModel\OrderCedynaThreshold\Collection $collection */
        $collection = $factory->getCollection();
        $collection->addFieldToFilter(
            'order_id', $orderId
        )->addFieldToFilter(
            'is_actived', 1
        )->addFieldToFilter(
            'is_cancelled', 0
        );

        if($collection->getSize()){
            return $collection->setPageSize(1)->getFirstItem();
        } else {
            return false;
        }
    }
}
