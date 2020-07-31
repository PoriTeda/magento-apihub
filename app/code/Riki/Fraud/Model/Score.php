<?php
namespace Riki\Fraud\Model;

class Score extends \Mirasvit\FraudCheck\Model\Score
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_orderCollection;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_serializeHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $_priceHelper;
    /**
     * @var \Riki\Fraud\Helper\SuspectedFraud
     */
    protected $_suspectedFraud;

    /**
     * Score constructor.
     *
     * @param \Magento\Variable\Model\VariableFactory $variableFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Serialize\Serializer\Json $serialized
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Mirasvit\FraudCheck\Rule\Pool $pool
     * @param \Mirasvit\FraudCheck\Model\Config $config
     * @param \Mirasvit\FraudCheck\Model\Context $context
     * @param \Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Riki\Fraud\Helper\SuspectedFraud $suspectedFraud
     */
    public function __construct(
        \Magento\Variable\Model\VariableFactory $variableFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Serialize\Serializer\Json $serialized,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceHelper,
        \Psr\Log\LoggerInterface $logger,
        \Mirasvit\FraudCheck\Rule\Pool $pool,
        \Mirasvit\FraudCheck\Model\Config $config,
        \Mirasvit\FraudCheck\Model\Context $context,
        \Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Riki\Fraud\Helper\SuspectedFraud $suspectedFraud
    ){
        parent::__construct($pool, $config, $context, $variableFactory, $ruleCollectionFactory, $logger);

        $this->_eavConfig = $eavConfig;
        $this->_orderCollection = $orderCollection;
        $this->_customerRepository = $customerRepository;
        $this->_storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_serializeHelper = $serialized;
        $this->_dateTime = $dateTime;
        $this->_timeZone = $timezone;
        $this->_priceHelper = $priceHelper;
        $this->_suspectedFraud = $suspectedFraud;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    public function setOrder($order)
    {
        $contextOrder = $order;
        $this->context->extractOrderData($contextOrder);
        $this->setData('fraud_score', $order->getData('fraud_score'));
        $this->setData('fraud_status', $order->getData('fraud_status'));
        return $this;
    }

    /**
     * @param bool $force
     * @param bool $save
     * @return mixed
     */
    public function getFraudScore($force = false, $save = true)
    {
        if (!$this->getData('fraud_score') || $force) {
            $score = $this->calculateDefaultScore( true );
            $this->setData('fraud_score', $score);
            $this->setData('fraud_status', $this->getFraudStatus($score));
        }

        return $this->getData('fraud_score');
    }

    /**
     * Get fraud status base on order data
     *
     * @param int $score
     * @return mixed|string
     */
    public function getFraudStatus($score)
    {
        $status = self::STATUS_APPROVE;

        if (!$this->context->order->getData('fraud_status')) {
            foreach ($this->getUserRules() as $rule) {
                $ruleStatus = $rule->getFraudStatus();
                if ($ruleStatus == self::STATUS_REJECT) {
                    $status = self::STATUS_REJECT;
                } elseif ($ruleStatus == self::STATUS_REVIEW && $status != self::STATUS_REJECT) {
                    $status = self::STATUS_REVIEW;
                }
            }
        } else {
            $status = $this->context->order->getData('fraud_status');
        }

        return $status;
    }

    /*get fraud status for order by one rule*/

    /**
     * Get fraud status by rule
     *
     * @param $rule
     * @param $order
     * @return string
     */
    public function getFraudStatusByRule( $rule, $order )
    {
        /*default status is approve*/
        $status = self::STATUS_APPROVE;

        $this->context->extractOrderData($order);

        $currentGrandTotal = $order->getData('grand_total');

        if ($rule->getData('duration') && $rule->getData('accumulated_type')) {
            $this->context->order->setData( 'grand_total', $this->calculateCustomerOrderTotal( $order, $rule ) );
        } else {
            $this->context->order->setData( 'grand_total', $currentGrandTotal );
        }

        $ruleStatus = $rule->getFraudStatus();

        if ($ruleStatus == self::STATUS_REJECT) {
            $status = self::STATUS_REJECT;
        } elseif ($ruleStatus == self::STATUS_REVIEW ) {
            $status = self::STATUS_REVIEW;
        }

        $this->context->order->setData( 'grand_total', $currentGrandTotal );

        return $status;
    }

    /**
     * Calculate default score before validate rule
     *
     * @param bool $default
     * @return float|int
     */
    protected function calculateDefaultScore($default = false)
    {
        $score = 0;
        $rulesResult = [];

        if ($default) {
            $totalImportance = 0;
            foreach ($this->getRules() as $rule) {
                if ($rule->isActive()) {
                    $totalImportance += pow(2, $rule->getImportance());
                    $rulesResult[] = $rule;
                }
            }

            foreach ($rulesResult as $rule) {
                $score += $rule->getFraudScore() * (pow(2, $rule->getImportance()) / $totalImportance);
            }

            $score++;
            $score = 100 - ($score / 2) * 100;
            $score = round($score);
        }

        return $score;
    }

    /**
     * Get list rules are apply for this order
     *
     * @param $order
     * @return array
     */
    public function getFraudObject($order)
    {
        /*add order data for this object*/
        $this->context->extractOrderData($order);

        /*current order grand total*/
        $currentGrandTotal = $order->getData('grand_total');

        $rs = [];

        /*default status is approved*/
        $status = self::STATUS_APPROVE;

        /*email list to send notification email for rules which status is review or approve */
        $warningRule = [];

        /*warning message*/
        $warningMessage = [];

        /*email list to send notification email for rules which status is reject */
        $rejectRule = [];

        /*reject message*/
        $rejectMessage = [];

        /*get list rule and validate for this order*/
        foreach ($this->getUserRules() as $rule) {

            /*this rule need calculate grand total during custom duration*/
            if ($rule->getData('duration') && $rule->getData('accumulated_type')) {

                /*set order grand total again with new value is accumulated*/
                $this->context->order->setData( 'grand_total', $this->calculateCustomerOrderTotal( $order, $rule ) );
            } else {

                /*set order grand total again with current value for any rules which do not need calculate grand total*/
                $this->context->order->setData( 'grand_total', $currentGrandTotal );
            }

            /*validate this order by this rule*/
            $ruleStatus = $rule->getFraudStatus();

            if ($ruleStatus == self::STATUS_REJECT) {

                $status = self::STATUS_REJECT;

                /*get reject rule id*/
                array_push($rejectRule, $rule->getRuleId());

                /*get reject message*/
                if (!empty($rule->getWarningMessage())) {
                    array_push($rejectMessage, $rule->getWarningMessage());
                }
            } elseif ($ruleStatus == self::STATUS_REVIEW || $ruleStatus == self::STATUS_APPROVE) {

                /*do not replace reject status - only change if status of this rule is review*/
                if ($status != self::STATUS_REJECT && $ruleStatus == self::STATUS_REVIEW) {
                    $status = self::STATUS_REVIEW;
                }

                /*get reject rule id*/
                array_push($warningRule, $rule->getRuleId());

                /*get reject message*/
                if (!empty($rule->getWarningMessage())) {
                    array_push($warningMessage, $rule->getWarningMessage());
                }
            }
        }

        $rs['status'] = $status;
        $rs['score'] = $this->calculateDefaultScore(true);
        $rs['warningRule'] = $warningRule;
        $rs['warningMessage'] = $warningMessage;
        $rs['rejectRule'] = $rejectRule;
        $rs['rejectMessage'] = $rejectMessage;

        /*set order grand total again, make sure we do not change any order data after validate process is stopped*/
        $this->context->order->setData( 'grand_total', $currentGrandTotal );

        return $rs;
    }

    /**
     * send notification for list rule id
     *
     * @param $order
     * @param $rule [] list rule id
     * @return bool
     */
    public function emailWarning($order, $rule)
    {
        try{
            if (!empty($rule)) {
                $collection = $this->ruleCollectionFactory->create()
                    ->addFieldToFilter('rule_id', array( 'in' => $rule));
                if ($collection->getSize()) {
                    foreach ($collection as $rl) {

                        $verifyFraudEmails = $this->verifyFraudEmails($rl);

                        if (!empty($rl->getData('send_email_to')) && !empty($rl->getData('email_template')) && $verifyFraudEmails == true) {
                            $this->sendEmailWarning( $order, $rl );
                        }
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $rule
     * @return bool
     */
    public function verifyFraudEmails($rule)
    {
        $emails = explode(';', str_replace(' ', '', $rule->getData('send_email_to')));
        foreach ($emails as $email)
        {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $order
     * @param $rule
     * @return mixed
     */
    protected function calculateCustomerOrderTotal($order, $rule)
    {
        $createdAt = $order->getCreatedAt();
        $modifyAt = new \DateTime( $createdAt );
        $modifyAt->modify('-'.$rule->getDuration().' '.$rule->getAccumulatedType());
        $orderCollection = $this->_orderCollection->create();
        $orderCollection->addAttributeToFilter('customer_email', $order->getCustomerEmail())
            ->addAttributeToFilter( 'created_at', ['from' => $modifyAt->format('Y-m-d H:i:s'), 'to' => $order->getCreatedAt(), 'datetime' => true])
            ->getSelect()->columns('SUM( grand_total ) as grand_total');

        if ($orderCollection->getSize()) {

            $total = $orderCollection->setPageSize(1)->getFirstItem()->getData('grand_total');

            if (!$order->getId()) {
                $total += $order->getData('grand_total');
            }

            return $total;
        } else {
            return $order->getData('grand_total');
        }
    }

    /**
     * Get attribute value from rule condition
     *
     * @param $rule
     * @param $attribute
     * @return bool
     */
    protected function getRuleAttributeValue($rule, $attribute)
    {
        $rs = false;

        $collection = $this->ruleCollectionFactory->create()->addFieldToFilter('rule_id', $rule->getRuleId());

        if ($collection->getSize()) {

            $rule = $collection->setPageSize(1)->getFirstItem();

            /*get rule condition*/
            $conditions = $rule->getConditionsSerialized();

            if (!empty( $conditions )) {
                /*unserialize this rule*/
                $unserCdt = $this->_serializeHelper->unserialize($conditions);

                if (!empty( $unserCdt ) && !empty( $unserCdt['conditions'] )) {
                    foreach ($unserCdt['conditions'] as $cdt) {
                        if ($cdt[ 'attribute' ] == $attribute) {
                            $rs = $cdt['value'];
                            break;
                        }
                    }
                }
            }
        }

        return $rs;
    }

    /**
     * send warning email
     *
     * @param $order
     * @param $rule
     */
    protected function sendEmailWarning($order, $rule)
    {
        $this->inlineTranslation->suspend();
        $this->generateTemplate( $order, $rule);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * Generate email template
     * @param $order
     * @param $rule
     * @return $this
     */
    protected function generateTemplate($order, $rule)
    {
        /* controlled by Email Marketing *./
        /* Email : Fraud notification (Business user) */
        $variables = $this->getEmailVariables($order,$rule);
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier( $rule->getEmailTemplate() )
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($variables)
            ->setFrom($senderInfo)
            ->addTo( explode( ';', str_replace(' ', '', $rule->getSendEmailTo()) ) );
        return $this;
    }

    /**
     * Get defautl sender email from config
     *
     * @return mixed
     */
    protected function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }

    /**
     * Get default sender name from config
     *
     * @return mixed
     */
    protected function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }

    /**
     * @param $order
     * @param bool $save
     * @return $this|bool
     */
    public function checkFraudScore($order, $save = true)
    {
        $fraudCheck = $this->getFraudObject($order);

        if ($order->getFraudScore() != $fraudCheck['score']
            || $order->getFraudStatus() != $fraudCheck['status']
        ) {
            $order->setFraudScore($fraudCheck['score']);
            $order->setFraudStatus($fraudCheck['status']);
        }

        /*check this order, change status and state*/
        if ($fraudCheck['status'] == \Riki\Fraud\Model\Score::STATUS_REVIEW) {
            /*add new record to tracking suspicious order*/
            $this->_suspectedFraud->suspectedOrder($order);
            /*change order data*/
            $order->setStatus(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            /*add history - fraud detected*/
            $order->addStatusToHistory(\Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS, __('Fraud system detects a fraud'));
            if ($save) {
                try {
                    $order->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                }
            }
            /*send notification for review list rules*/
            $this->emailWarning($order, $fraudCheck['warningRule']);
        }

        return $fraudCheck;
    }

    /**
     * @param $order
     * @return $this
     */
    public function setFraudData($order)
    {
        $fraudCheck = $this->getFraudObject($order);

        if( $order->getFraudScore() != $fraudCheck['score']
            || $order->getFraudStatus() != $fraudCheck['status'] ){
            $order->setFraudScore($fraudCheck['score']);
            $order->setFraudStatus($fraudCheck['status']);
        }
        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $rule
     * @return array
     */
    public function getEmailVariables(\Magento\Sales\Model\Order $order, $rule)
    {
        $customerMembership = '';
        $customerDbId = '';

        try{
            $customer = $this->_customerRepository->getById($order->getCustomerId());
            $customerMembershipRaw = $customer->getCustomAttribute('membership')->getValue();
            $customerMembership = $this->getAttributesOption($customerMembershipRaw);

            if( !empty($customer->getCustomAttribute('consumer_db_id')) ) {
                $customerDbId = $customer->getCustomAttribute('consumer_db_id')->getValue();
            }
        } catch(\Exception $e) {
            $this->logger->info($e->getMessage());
        }

        $shipmentIds = array();
        $shipments = $order->getShipmentsCollection();

        if ($shipments) {
            foreach ($shipments as $_ship) {
                $shipmentIds[] = $_ship->getIncrementId();
            }
        }

        $orderCreatedRaw = $this->_timeZone->formatDateTime($order->getCreatedAt(), 2,2);
        $orderDate = $this->_dateTime->gmtDate('Y-m-d H:i:s', $orderCreatedRaw);
        $variables = [];
        $variables['order_increment_id']=  !empty($order->getId()) ? $order->getIncrementId() : '';
        $variables['order_date']=  $orderDate;
        $variables['shipment_increment_id'] = implode(",",$shipmentIds);
        $variables['customer_db_id'] = $customerDbId;
        $variables['customer_membership']= $customerMembership;
        $variables['base_grand_total'] = $this->_priceHelper->format($this->getRuleAttributeValue($rule, 'grand_total'), false);
        $variables['current_grand_total']= $this->_priceHelper->format($order->getGrandTotal(), false);
        $variables['blacklist_reason']= $rule->getName();
        $variables['blacklist_field'] = $this->getRulesField($rule->getData('conditions_serialized'));
        return $variables;
    }

    /**
     * @param $values
     * @return string
     */
    public function getAttributesOption($values)
    {
        $newvalues = array();
        if ($values) {
            $optvals = explode(',',$values);
            if ($optvals) {
                $attribute = $this->_eavConfig->getAttribute('customer', 'membership');
                $options = $attribute->getSource()->getAllOptions();
                foreach ($optvals as $_val) {
                    foreach ($options as $_opt) {
                        if ($_opt['value']==$_val) {
                            $newvalues[] = $_opt['label']->getText();
                        }
                    }
                }
            }
        }
        return implode(",",$newvalues);
    }

    /**
     * @param $conditions
     * @return string
     */
    public function getRulesField($conditions)
    {
        $ruleFields = array();
        if ($conditions) {
            $fields = $this->_serializeHelper->unserialize($conditions);
            if ($fields['conditions']) {
                foreach ($fields['conditions'] as $cond) {
                    $ruleFields[] = ucfirst($cond['attribute']);
                }
            }
        }
        return implode(",",$ruleFields);
    }

    /**
     * Check this order is fraud order
     *
     * @param $order
     * @return bool
     */
    public function isFraudOrder($order)
    {
        /*check fraud for this order with all current rules*/
        $fraudCheck = $this->getFraudObject($order);

        /*check this order, change status and state*/
        if ($fraudCheck['status'] == \Riki\Fraud\Model\Score::STATUS_REVIEW) {
            return true;
        }

        return false;
    }
}
