<?php
namespace Riki\DelayPayment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\SubscriptionCourse\Model\Course;

class Data extends AbstractHelper
{
    const VALIDATE_SUBSCRIPTION_COURSE_THRESHOLD = 'validate_subscription_course_threshold';

    const CONFIG_DELAY_PAYMENT_CANCEL_AUTHORIZE_ACTIVE = 'paygent_config/delay_payment/active';

    const CONFIG_DELAY_PAYMENT_CANCEL_AUTHORIZE_ENABLE_LOGGER = 'paygent_config/delay_payment/enable_logger';

    const PAYMENT_AGENT_NICOS = 'NICOS';

    const PAYMENT_AGENT_JCB = 'JCB';

    const CART_RULE_SIMPLE_TYPE_ENABLE_DELAY_PAYMENT_2ND_ORDER = [
        'cart_fixed',
        'by_fixed',
        'by_percent'
    ];
    /**
     * @var \Riki\DelayPayment\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    protected $courseData = [];

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $dataObj;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Riki\DelayPayment\Logger\Logger $logger
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Magento\Framework\DataObject $dataObj
     */
    public function __construct(
        Context $context,
        \Riki\DelayPayment\Logger\Logger $logger,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Framework\DataObject $dataObj
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->courseFactory = $courseFactory;
        $this->profileRepository = $profileRepository;
        $this->quoteFactory = $quoteFactory;
        $this->simulator = $simulator;
        $this->dataObj = $dataObj;
    }

    /**
     * check active cron job
     *
     * @return mixed
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_DELAY_PAYMENT_CANCEL_AUTHORIZE_ACTIVE, $storeScope);
    }
    /**
     * check enable/disable logger
     *
     * @return mixed
     */
    public function enableLogger()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_DELAY_PAYMENT_CANCEL_AUTHORIZE_ENABLE_LOGGER, $storeScope);
    }

    /**
     * @param $message
     */
    public function writeToLog($message)
    {
        if ($this->enableLogger()) {
            $this->logger->info($message);
        }
    }

    /**
     * Check order is delay payment
     *
     * @param $order
     * @param null $quote
     * @return bool
     */
    public function checkOrderDelayPayment($order, $quote = null)
    {
        if ($order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT) {
            return true;
        }
        /** for generate order */
        if ($order->getData('profile_id') && $courseId = $this->getCourseIdByProfileId($order->getData('profile_id'))) {
            return $this->checkIsDelayPaymentByCourseId($courseId);
        }
        /** for place order */
        if ($quote != null && $quote->getRikiCourseId()) {
            return $this->checkIsDelayPaymentByCourseId($quote->getRikiCourseId());
        }

        /** for cron */
        if ($order->getQuoteId()) {
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $courseId = $quote->getRikiCourseId();
            if ($courseId) {
                return $this->checkIsDelayPaymentByCourseId($quote->getRikiCourseId());
            }
        }
        return false;
    }

    /**
     * Change NICOS to NICOS2 , JCB to JCB2 when order is delay payment
     *
     * @param $paymentAgent
     * @return string
     */
    public function convertPaymentAgentDelayPayment($paymentAgent)
    {
        $listAgents = [self::PAYMENT_AGENT_NICOS, self::PAYMENT_AGENT_JCB];
        if (in_array($paymentAgent, $listAgents)) {
            return $paymentAgent . '2';
        }
        return $paymentAgent;
    }

    /**
     * Get course id
     *
     * @param $profileId
     * @return mixed|null
     */
    protected function getCourseIdByProfileId($profileId)
    {
        try {
            $profile = $this->profileRepository->get($profileId);
            return $profile->getCourseId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Check course ís delay payment
     *
     * @param $courseId
     * @return bool
     */
    protected function checkIsDelayPaymentByCourseId($courseId)
    {
        $courseModel = $this->courseFactory->create()->load($courseId);
        return $courseModel->isDelayPayment();
    }

    /**
     * Load course factory
     *
     * @param $courseId
     * @return mixed
     */
    public function loadCourse($courseId) {
        if (isset($this->courseData[$courseId])) {
            return $this->courseData[$courseId];
        }
        $courseModel = $this->courseFactory->create()->load($courseId);
        $this->courseData[$courseId] = $courseModel;
        return $this->courseData[$courseId];
    }

    /**
     * Check is valid order delay payment
     * @param $subscriptionCourse
     * @param $profileData
     * @return bool
     */
    public function isValidOrderDelayPayment($subscriptionCourse, $profileData)
    {
        if ($this->isDelayPaymentSubCourse($subscriptionCourse)) {
            if ($this->applyOrderTotalAmountThresholdValidating($subscriptionCourse, $profileData)) {
                $simulatorOrder = $this->simulator->createSimulatorOrderHasData($profileData);
                if ($simulatorOrder) {
                    if (!$this->validateOrderTotalAmountThreshold($subscriptionCourse, $simulatorOrder)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Validate order total amount threshold
     * @param $subscriptionCourse
     * @param $simulatorOrder
     * @return bool
     */
    public function validateOrderTotalAmountThreshold($subscriptionCourse, $simulatorOrder)
    {
        $grandTotal = $simulatorOrder->getGrandTotal();
        if ($simulatorOrder instanceof \Magento\Quote\Model\Quote) {
            $totals = $simulatorOrder->getTotals();
            if (isset($totals['subtotal'])) {
                $subTotal = $totals['subtotal'];
                $grandTotal = $subTotal->getData('value_incl_tax');
            }
        }
        $subCourseThreshold = $subscriptionCourse->getData('total_amount_threshold');
        if ($subCourseThreshold > 0 && $grandTotal < $subCourseThreshold) {
            return false;
        }
        return true;
    }

    /**
     * Check is 2nd Order
     * @param $profileData
     * @return bool
     */
    public function is2ndOrder($profileData)
    {
        if ($profileData->getOrderTimes() == 1) {
            return true;
        }
        return false;
    }

    /**
     * Check is delay payment sub course
     * @param $subscriptionCourse
     * @return bool
     */
    public function isDelayPaymentSubCourse($subscriptionCourse)
    {
        if ($subscriptionCourse->getData('is_delay_payment')) {
            return true;
        }
        return false;
    }

    /**
     * @param $profile
     * @param $originProfile
     * @return \Magento\Framework\Phrase
     */
    public function getOrderDelayPaymentError($profile, $originProfile)
    {
        $originProductCart = [];
        foreach ($originProfile->getProductCartData() as $orgCartId => $orgProduct) {
            $orgProductId = $orgProduct->getData('product_id');
            $originProductCart[$orgCartId][$orgProductId] = $orgProduct->getData('qty');
        }
        $updatedProductCart = $profile->getData('product_cart');
        if (count($originProductCart) > count($updatedProductCart)) {
            return __('The product is not allow to delete due to it\'s below total amount threshold');
        }
        foreach ($updatedProductCart as $cartId => $product) {
            $productId = $product->getData('product_id');
            if (isset($originProductCart[$cartId][$productId])) {
                if ($product->getData('qty') != $originProductCart[$cartId][$productId]) {
                    return __('The product is not allow to change quantity due to it\'s below total amount threshold');
                }
            }
        }
        return '';
    }

    /**
     * @param $productCartData
     * @return array
     */
    public function cloneProductCartData($productCartData)
    {
        $returnData = [];
        foreach ($productCartData as $cartId => $productObj) {
            $returnData[$cartId] = clone $productObj;
        }
        return $returnData;
    }

    /**
     * @param $order
     * @param $subCourse
     * @param $profile
     * @return bool
     */
    public function hasValidSubCourseThreshold($order, $subCourse, $profile)
    {
        if ($this->isDelayPaymentSubCourse($subCourse)) {
            if ($this->applyOrderTotalAmountThresholdValidating($subCourse, $profile)) {
                if (!$this->validateOrderTotalAmountThreshold($subCourse, $order)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Apply order total amount threshold validating
     * @param $subscriptionCourse
     * @param $profileData
     * @return bool
     */
    public function applyOrderTotalAmountThresholdValidating($subscriptionCourse, $profileData)
    {
        $orderTotalAmountOption = $subscriptionCourse->getData('order_total_amount_option');
        if (Course::TOTAL_AMOUNT_OPTION_ALL_ORDER == $orderTotalAmountOption) {
            return true;
        } else {
            if ($this->is2ndOrder($profileData)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Prepare first order simulator parameter
     * @param $quote
     * @param null $subCourseId
     * @return array
     */
    public function prepareFirstOrderSimulatorParameters($quote, $subCourseId = null)
    {
        if (!$subCourseId) {
            $subCourseId = $quote->getData('riki_course_id');
        }
        /** @var \Magento\Quote\Model\Quote $simulatorQuote */
        $simulatorQuote = clone $quote;
        $simulatorQuote->getBillingAddress();
        $simulatorQuote->getShippingAddress()->setCollectShippingRates(true);
        $simulatorQuote->collectTotals();
        $totals = $simulatorQuote->getTotals();
        if (isset($totals['subtotal'])) {
            $subTotal = $totals['subtotal'];
            $subTotalInclTax = $subTotal->getData('value_incl_tax');
            $simulatorQuote->setData('grand_total', $subTotalInclTax);
        }
        $subCourse = $this->loadCourse($subCourseId);
        return [$simulatorQuote, $subCourse, $this->dataObj];
    }
}
