<?php
/**
 * Subscription Email
 * PHP version 7.
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\SubscriptionEmail\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Riki\EmailMarketing\Helper\Order as OrderHelper;
use Riki\EmailMarketing\Helper\Data as EmailDataHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Riki\DeliveryType\Model\Delitype;
/**
 * Class OrderSender.
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $_emailHelper;

    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $_preOrderHelper;

    /**
     * @var
     */
    protected $_rewardFactory;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var EmailDataHelper
     */
    protected $emailDataHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subscriptionCourseFactory;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /* @var \Magento\Quote\Model\QuoteRepository */
    protected $quoteRepository;

    /* @var \Riki\SubscriptionEmail\Helper\Data */
    protected $subEmailHelperData;

    /* @var \Magento\Framework\Registry */
    protected $registry;

    /* @var \Riki\SubscriptionPage\Helper\Data */
    protected $subPageHelperData;

    protected $_subscriptionProfileSenderBuilderFactory;

    protected $_profile;

    protected $_simulator;

    protected $appEmulation;

    /**
     * OrderSender constructor.
     *
     * @param Template                                           $templateContainer
     * @param OrderIdentity                                      $identityContainer
     * @param Order\Email\SenderBuilderFactory                   $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param Renderer                                           $addressRenderer
     * @param PaymentHelper                                      $paymentHelper
     * @param OrderResource                                      $orderResource
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param ManagerInterface                                   $eventManager
     * @param \Riki\Sales\Helper\Email                           $email
     * @param \Riki\Preorder\Helper\Data                         $preOrder
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory    $rewardFactory
     * @param OrderHelper                                        $orderHelper
     * @param EmailDataHelper                                    $emailDataHelper
     * @param \Riki\SubscriptionCourse\Model\CourseFactory       $courseFactory
     * @param PriceHelper                                        $priceHelper
     */
    public function __construct(
        \Riki\SubscriptionPage\Helper\Data $subPageHelperData,
        \Magento\Framework\Registry $registry,
        \Riki\SubscriptionEmail\Helper\Data $subEmailHelperData,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        Template $templateContainer,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        \Riki\Sales\Helper\Email $email,
        \Riki\Preorder\Helper\Data $preOrder,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardFactory,
        OrderHelper $orderHelper,
        EmailDataHelper $emailDataHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        PriceHelper $priceHelper,
        \Riki\SubscriptionEmail\Model\Order\Email\SubscriptionProfileSenderBuilderFactory $subscriptionProfileSenderBuilderFactory,
        \Riki\Subscription\Model\Profile\Profile\Proxy $profile,
        \Riki\Subscription\Helper\Order\Simulator\Proxy $simulator,
        \Magento\Store\Model\App\Emulation $emulation
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );

        $this->subPageHelperData = $subPageHelperData;
        $this->registry = $registry;
        $this->subEmailHelperData = $subEmailHelperData;
        $this->quoteRepository = $quoteRepository;
        $this->_emailHelper = $email;
        $this->_preOrderHelper = $preOrder;
        $this->orderHelper = $orderHelper;
        $this->emailDataHelper = $emailDataHelper;
        $this->subscriptionCourseFactory = $courseFactory;
        $this->priceHelper = $priceHelper;
        $this->_subscriptionProfileSenderBuilderFactory = $subscriptionProfileSenderBuilderFactory;
        $this->_profile = $profile;
        $this->_simulator = $simulator;
        $this->appEmulation = $emulation;
    }

    /**
     * @param Order $order
     * @param bool  $forceSyncMode
     *
     * @return bool
     */
    public function send(Order $order, $forceSyncMode = false)
    {
        /*No need to update order attribute "send_email" when simulating*/
        if($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return false;
        }
        $order->setSendEmail(true);
        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            if ($this->checkAndSend($order)) {
                $order->setEmailSent(true);
                $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);

                return true;
            }
        }

        $this->orderResource->saveAttribute($order, 'send_email');

        return false;
    }

    /**
     * @param Order $order
     */
    protected function prepareTemplate(Order $order)
    {
        $this->appEmulation->startEnvironmentEmulation($order->getStoreId(), 'frontend', true);

        /* controlled by Email Marketing */
        /* Send confirmation order email */
        $isEditOrder = $order->getRelationParentId();

        //ticket 6469
        $transport = $this->orderHelper->getOrderVariables($order,'spot_confirmation_order');
        $hanpukaiVariable = array();
        $subscriptionProfileId = $order->getData('subscription_profile_id');
        if (!$isEditOrder) {
            //new order

            $isHanpukai = $this->isHanpukaiOrder($order);
            if ($isHanpukai) {
                //hanpukai order
                $templateId = $this->emailDataHelper->getTempalteEmailOrderConfirmationHanpukai();
            } else {
                if($this->_isMultiShipmentsOrder($order))
                {
                    //multi shipment
                    $templateId = $this->emailDataHelper->getTempalteEmailOrderConfirmationSpotMulti();
                }
                else
                {
                    //single shipment
                    $templateId = $this->emailDataHelper->getTempalteEmailOrderConfirmationSpot();
                }

            }
        } else {
            //edit order

            if ($subscriptionProfileId) {
                // Subscription

                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSubscription();
            } else {
                $templateId = $this->emailDataHelper->getTempalteEmailOrderChangeSpot();
            }
        }
        //set email variable
        $this->eventManager->dispatch(
            'email_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transport]
        );

        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());
        $this->templateContainer->setTemplateVars($transport);
        $customerName = $order->getCustomerName();
        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($order->getCustomerEmail());
        $this->templateContainer->setTemplateId($templateId);

        $this->appEmulation->stopEnvironmentEmulation();
    }

    public function isHanpukaiOrder(\Magento\Sales\Model\Order $order)
    {

        $rikiType = '';
        try {

            $quote = $this->getQuoteById($order->getQuoteId(), $order->getStoreId());

            if ($quote->getRikiCourseId()) {
                $rikiCourseId = $quote->getRikiCourseId();
            } elseif ($order->getSubscriptionProfileId()) {
                $subscriptionProfileModelObj = $this->getSubscriptionProfileModel()->load($order->getSubscriptionProfileId());
                $rikiCourseId = $subscriptionProfileModelObj->getCourseId();
            } else {
                $rikiCourseId = null;
            }

            if ($rikiCourseId == null) {
                return false;
            }

            $courseFactory = $this->subscriptionCourseFactory->create()->load($rikiCourseId);
            if ($courseFactory->getSubscriptionType() == 'subscription') {
                $rikiType = 'subscription';
            } else {
                $rikiType = 'hanpukai';
            }
        } catch (\Exception $e) {
            $rikiType = 'subscription';
            $this->logger->info($e->getMessage());
        }

        if ($rikiType == 'hanpukai') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function prepareHanpukaiVariables(\Magento\Sales\Model\Order $order)
    {
        $quote = $this->getQuoteById($order->getQuoteId(), $order->getStoreId());
        $pointEarn = 0;
        $pointUsed = $this->getPointUse();
        $giftWrappingFee = $this->getWrappingFee($order);

        if ($quote->getRikiCourseId()) {
            $rikiCourseId = $quote->getRikiCourseId();
        } elseif ($order->getSubscriptionProfileId()) {
            $subscriptionProfileModelObj = $this->getSubscriptionProfileModel()->load($order->getSubscriptionProfileId());
            $rikiCourseId = $subscriptionProfileModelObj->getCourseId();
        } else {
            $rikiCourseId = null;
        }

        if ($rikiCourseId == null) {
            $transport = [
                'order' => $order,
                'billing' => $order->getBillingAddress(),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            ];
        } else {
            try {
                if ($quote->getRikiFrequencyId()) {
                    $courseFrequency = $this->getCourseFrequencyByFrequencyId($quote->getRikiFrequencyId());
                } else {
                    $subscriptionProfileModelObj = $this->getSubscriptionProfileModel()->load($order->getSubscriptionProfileId());
                    $courseFrequency = $subscriptionProfileModelObj->getFrequencyInterval().' '.$subscriptionProfileModelObj->getFrequencyUnit();
                }
            } catch (\Exception $e) {
                $courseFrequency = '';
                $this->logger->info($e->getMessage());
            }

            $payment = $order->getPayment();

            try {
                if ($this->getHelper()->getDeliveryDate()) {
                    $deliveryDate = $this->getHelper()->getDeliveryDate();
                } else {
                    $deliveryDate = null;
                    $subscriptionProfileId = $order->getSubscriptionProfileId();

                    $arrProfileIdProductId = $this->getHelper()->getProductAndProductCartIdFromSubscriptionProfileProductCart($subscriptionProfileId);
                    $listDeliveryType = $this->getHelper()->splitQuoteByDeliveryType($arrProfileIdProductId);

                    foreach ($listDeliveryType as $deliveryType => $arrProductId) {
                        if (count($arrProductId) > 0) {
                            $subscriptionProductCartId = $arrProfileIdProductId[$arrProductId[0]];
                            $arrDeliveryDateTimeSlot = $this->getHelper()->getDeliveryDateAndTimeSlot($subscriptionProductCartId);
                            $deliveryDate[$deliveryType] = $arrDeliveryDateTimeSlot;
                        }
                    }

                    if ($deliveryDate) {
                        $deliveryDate['fromSubscriptionProfileProductCart'] = 1;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $deliveryDate = null;
            }

            $paymentFee = $this->getHelper()->getFormatPrice($order->getData('fee'));
            $hanpukaiTotalDelivery = $this->getHanpukaiTotalDelivery($rikiCourseId, $courseFrequency);
            $subscriptionTitle = $this->getSubscriptionTitle($rikiCourseId, $courseFrequency, $order->getSubscriptionProfileId());
            if (isset($subscriptionTitle['subscription_name'])) {
                $subscriptionName = $subscriptionTitle['subscription_name'];
            } else {
                $subscriptionName = '';
            }

            if (isset($subscriptionTitle['subscription_frequency'])) {
                if (strpos($subscriptionTitle['subscription_frequency'], 'month') !== false) {
                    $subscriptionFrequency = str_replace('month', __('month'), $subscriptionTitle['subscription_frequency']);
                } elseif (strpos($subscriptionTitle['subscription_frequency'], 'week') !== false) {
                    $subscriptionFrequency = str_replace('week', __('week'), $subscriptionTitle['subscription_frequency']);
                } else {
                    $subscriptionFrequency = $subscriptionTitle['subscription_frequency'];
                }
            } else {
                $subscriptionFrequency = '';
            }

            if (isset($subscriptionTitle['subscription_delivery_number'])) {
                $subscriptionDeliveryNumber = $subscriptionTitle['subscription_delivery_number'];
            } else {
                $subscriptionDeliveryNumber = '';
            }

            if (!isset($subscriptionProfileId)) {
                $subscriptionProfileId = null;
            }

            // $arrData ['course_id' => $courseId, 'frequency_unit' => $frequencyUnit, 'frequency_interval' => $frequencyInterval]
            $arrCourseFrequency = explode(' ', $courseFrequency);
            if (count($arrCourseFrequency) < 1) {
                $arrCourseFrequency[0] = '';
                $arrCourseFrequency[1] = '';
            }
            $arrDataNeeded = [
                'course_id' => $rikiCourseId,
                'frequency_unit' => $arrCourseFrequency[1],
                'frequency_interval' => $arrCourseFrequency[0],
            ];
            try {
                $hanpukaiTotalAmountNextDelivery = $this->getHanpuakiAmountTotalNextDelivery($order, $subscriptionProfileId, $arrDataNeeded);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $hanpukaiTotalAmountNextDelivery = 'Not Calculate';
            }
            $courseObject = $this->subscriptionCourseFactory->create()->load($rikiCourseId);
            $transport = [
                'order' => $order,
                'billing' => $order->getBillingAddress(),
                'store' => $order->getStore(),
                'formattedShippingAddressText' => $this->getFormattedShippingAddressText($order),
                'formattedBillingAddressText' => $this->getFormattedBillingAddressText($order),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'course_frequency' => $courseFrequency,
                'increment_id' => $order->getIncrementId(),
                'created_order' => $order->getCreatedAtFormatted(2),
                'grand_total_order' => $order->getGrandTotal(),
                'total_money_items' => $order->getSubtotalInclTax(),
                'shipping_fee' => $order->getShippingAmount(),
                'payment_method' => $payment->getMethodInstance()->getTitle(),
                'delivery_date' => $deliveryDate,
                'payment_fee' => $paymentFee,
                'point_earn' => $pointEarn,
                'point_use' => $pointUsed,
                'wrapping_fee' => $giftWrappingFee,
                'hanpukai_total_delivery' => $hanpukaiTotalDelivery,
                'hanpukai_total_amount_next_delivery' => $this->priceHelper->currency($hanpukaiTotalAmountNextDelivery, true, false),
                'total_amount_next_order' => $this->priceHelper->currency($hanpukaiTotalAmountNextDelivery, true, false),
                'subscription_name' => $subscriptionName,
                'subscription_frequency' => $subscriptionFrequency,
                'subscription_delivery_number' => $subscriptionDeliveryNumber,
                'subscription_type' => 'subscription',
                'hanpukai_course_name' => $courseObject->getName(),
                'subscription_course_name' => $courseObject->getName(),
                'order_number_time' => $courseObject->getData('hanpukai_maximum_order_times'),
            ];
        }

        $priceObj = new \Magento\Framework\DataObject();

        $priceObj->addData([
            'grand_total' => $order->formatPrice($order->getGrandTotal()),
            'sub_total' => $order->formatPrice($order->getSubtotal()),
            'gw_total' => $order->formatPrice($order->getGwPrice()),
            'shipping_amount' => $order->formatPrice($order->getShippingAmount()),
            'payment_fee' => $order->formatPrice($order->getFee()),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'used_point' => (int) $order->getUsedPoint(),
            'bonus_point_amount' => (int) $order->getBonusPointAmountt(),

        ]);

        $transport['price_obj'] = $priceObj;
        $transport['shipping'] = $order->getShippingAddress();

        return $transport;
    }

    /**
     * Get wrapping fee.
     *
     * @param $order
     *
     * @return int
     */
    public function getWrappingFee($order)
    {
        $giftWrappingFee = 0;
        try {
            if ($order->getData('gw_items_price_incl_tax')) {
                $giftWrappingFee = $order->getData('gw_items_price_incl_tax');
            }
        } catch (\Exception $e) {
            $giftWrappingFee = 0;
        }

        return $giftWrappingFee;
    }

    /**
     * @param $order
     */
    public function prepareTemplateFirst($order)
    {
        $this->templateContainer->setTemplateOptions($this->getTemplateOptions());

        if ($order->getCustomerIsGuest()) {
            $templateId = $this->identityContainer->getGuestTemplateId();
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = $this->identityContainer->getTemplateId();
            $customerName = $order->getCustomerName();
        }

        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($order->getCustomerEmail());
        $this->templateContainer->setTemplateId($templateId);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    protected function checkAndSend(Order $order)
    {
        $this->identityContainer->setStore($order->getStore());
        if (!$this->identityContainer->isEnabled()) {
            return false;
        }
        /*check and send email for preorder*/
        $isPreOrder = $this->_preOrderHelper->getOrderIsPreorderFlag($order);

        if ($isPreOrder) {
            $emailTemplateVariables = $this->orderHelper->getOrderVariables($order);
            $emailCustomer = trim($order->getCustomerEmail());
            $this->_emailHelper->sendMailConfirmationPreOrder($emailTemplateVariables, $emailCustomer);

            return true;
        }

        $this->prepareTemplate($order);

        /** @var \Riki\SubscriptionEmail\Model\Order\Email\SenderBuilder $sender */
        $sender = $this->getSender();

        try {
            $rikiCourseId = null;
            if($order->getData('subscription_profile_id')){
                if ($this->getQuoteById($order->getQuoteId(), $order->getStoreId())->getRikiCourseId() != null) {
                    $rikiCourseId = $this->getQuoteById($order->getQuoteId(), $order->getStoreId())->getRikiCourseId();
                } elseif ($order->getData('subscription_profile_id')) {
                    $subscriptionProfileModelObj = $this->getSubscriptionProfileModel()->load($order->getData('subscription_profile_id'));
                    $rikiCourseId = $subscriptionProfileModelObj->getCourseId();
                }
            }
            //send email
            $relationEntityId = $order->getIncrementId();
            $relationEntityType='confirmation_email';
            if ($rikiCourseId) {
                $subscriptionProfileModelObj = $this->getSubscriptionProfileModel()->load($order->getData('subscription_profile_id'));
                if ($subscriptionProfileModelObj->getOrderTimes() <= 1) {
                    //dont sent subscription and hanpukai order from second time
                    $sender->send($relationEntityId,$relationEntityType);
                    $sender->sendCopyTo();
                }
            } else {
                $sender->send($relationEntityId,$relationEntityType);
                $sender->sendCopyTo();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return true;
    }

    /**
     * @param $quoteId
     *
     * @return mixed
     */
    public function getQuoteById($quoteId, $storeId)
    {
        $sharedStoreIds[] = $storeId;
        return $this->quoteRepository->get($quoteId, $sharedStoreIds);
    }

    /**
     * @return mixed
     */
    public function getSenderForSubscriptionProfile()
    {
        return $this->_subscriptionProfileSenderBuilderFactory->create(
            [
                'templateContainer' => $this->templateContainer,
                'identityContainer' => $this->identityContainer,
            ]
        );
    }

    /**
     * @param $courseFrequencyId
     *
     * @return mixed
     */
    public function getCourseFrequencyByFrequencyId($courseFrequencyId)
    {
        $allFrequency = $this->getHelper()->getAllFrequency();

        return $allFrequency[$courseFrequencyId];
    }

    /**
     * @return mixed
     */
    public function getHelper()
    {
        return $this->subEmailHelperData;
    }

    /**
     * @param $order
     *
     * @return null|string
     */
    protected function getFormattedBillingAddressText($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'text');
    }

    /**
     * @param $order
     *
     * @return null|string
     */
    protected function getFormattedShippingAddressText($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'text');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return int
     */
    public function getPointEarn($order)
    {
        try {
            if ($pointEarn = $order->getData('bonus_point_amount')) {
                return $pointEarn;
            }
            /** @var \Riki\Loyalty\Model\ResourceModel\Reward $rewardModel */
            $rewardModel = $this->_rewardFactory->create();

            return $rewardModel->getTentative($order->getIncrementId());
        } catch (\Exception $e) {
            $this->logger->critical(__CLASS__.$e->getMessage());

            return 0;
        }
    }

    /**
     * @return int
     */
    public function getPointUse()
    {
        try {
            if ($pointUsed = $this->registry->registry('ampoints_used')) {
                return $pointUsed;
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            $this->logger->critical(__CLASS__.$e->getMessage());

            return 0;
        }
    }

    /**
     * @param $courseId
     *
     * @return mixed
     */
    public function getSubscriptionType($courseId)
    {
        return $this->getSubscriptionPageHelper()->getSubscriptionType($courseId);
    }

    /**
     * @return mixed
     */
    public function getSubscriptionPageHelper()
    {
        return $this->subPageHelperData;
    }

    /**
     * @param $courseId
     * @param $frequency
     *
     * @return string
     */
    public function getHanpukaiTotalDelivery($courseId, $frequency)
    {
        $subscriptionPageHelper = $this->getSubscriptionPageHelper();
        $hanpukaiType = $subscriptionPageHelper->getHanpukaiType($courseId);
        if ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_FIXED) {
            return '';
        } elseif ($hanpukaiType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI_SEQUENCE) {
            $totalDelivery = $subscriptionPageHelper->getTotalNumberDeliveryForHanpukaiSequency($courseId);
            return sprintf(__("All %s times"), count($totalDelivery));
        } else {
            return '';
        }
    }

    /**
     * @param $courseId
     * @param $frequency
     * @param $profileId
     *
     * @return array
     */
    public function getSubscriptionTitle($courseId, $frequency, $profileId)
    {
        $subscriptionPageHelper = $this->getSubscriptionPageHelper();
        $courseModel = $subscriptionPageHelper->getSubscriptionCourseModelFromCourseId($courseId);
        $profileModel = $this->getSubscriptionProfileModel()->load($profileId);

        try {
            if ($profileModel->getOrderTimes()) {
                $deliveryNumber = $profileModel->getOrderTimes();
            } else {
                $deliveryNumber = 1;
            }
            $title['subscription_name'] = $courseModel->getCourseName();
            $title['subscription_frequency'] = $frequency;
            $title['subscription_delivery_number'] = $deliveryNumber;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            $title = [];
        }

        return $title;
    }

    /**
     * @return mixed
     */
    public function getSubscriptionProfileModel()
    {
        return $this->_profile;
    }

    /**
     * @return mixed
     */
    public function getOrderHelperSimulator()
    {
        return $this->_simulator;
    }

    /**
     * @param $order
     * @param $subscriptionProfileId
     * @param $arrNeeded
     *
     * @return int|string
     */
    public function getHanpuakiAmountTotalNextDelivery($order, $subscriptionProfileId, $arrNeeded)
    {
        /* @var \Riki\Subscription\Helper\Order\Simulator $orderHelperSimulator  */
        $orderHelperSimulator = $this->getOrderHelperSimulator();
        $order = $orderHelperSimulator->simulateMageOrder($order, $subscriptionProfileId, $arrNeeded, true);

        if ($order === false) {
            return 'Sorry.System not calculate total amount next delivery';
        } elseif ($order === 0) {
            return 0;
        } else {
            return $order->getGrandTotal();
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function _isMultiShipmentsOrder
    (
        \Magento\Sales\Model\Order $order
    )
    {
        $assignation = $order->getAssignation();
        if($assignation)
        {
            $assignationData = \Zend_Json::decode($order->getAssignation(), \Zend_Json::TYPE_ARRAY);
            if(count(explode(',', $assignationData['place_ids'])) <2) // single warehouses
            {
                return false;
            } else // check share warehouse for 1 product
            {
                $productWh = array();
                foreach ($assignationData['items'] as $item)
                {
                    $arrPOS = $item['pos'];
                    foreach($arrPOS as $key=>$val)
                    {
                        $productWh[$item['product_id']][$key] = $val['qty_assigned'];
                    }
                }
                // check if product has multi warehouse
                $flagSplitShipment = false;
                foreach($productWh as $productKey=>$pWH)
                {
                    if(count($pWH) > 1)
                    {
                        $flagSplitShipment = true;
                    }
                }
                return $flagSplitShipment;
            }
        }
        else
        {
            return false;
        }
    }
}
