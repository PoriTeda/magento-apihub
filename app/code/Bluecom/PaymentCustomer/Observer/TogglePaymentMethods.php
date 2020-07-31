<?php

namespace Bluecom\PaymentCustomer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\App\Area\FrontNameResolver;

class TogglePaymentMethods implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Bluecom\PaymentCustomer\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * @var \Riki\CsvOrderMultiple\Logger\LoggerOrder
     */
    protected $csvOrderLogger;

    /**
     * Available constructor.
     *
     * @param \Magento\Customer\Model\Session              $customerSession Session
     * @param \Bluecom\PaymentCustomer\Helper\Data         $helperData      Data
     * @param \Magento\Checkout\Model\Session              $checkoutSession Session
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory   CourseFactory
     * @param \Magento\Backend\Model\Session\Quote         $sessionQuote    Quote
     * @param \Magento\Framework\Model\Context             $context         Context
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Bluecom\PaymentCustomer\Helper\Data $helperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\Model\Context $context,
        \Riki\CsvOrderMultiple\Logger\LoggerOrder $logger
    ) {
        $this->_customerSession = $customerSession;
        $this->_helperData = $helperData;
        $this->_checkoutSession = $checkoutSession;
        $this->_courseFactory = $courseFactory;
        $this->_sessionQuote = $sessionQuote;
        $this->_context = $context;
        $this->csvOrderLogger = $logger;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $result = $observer->getEvent()->getResult();
        $quote = $observer->getEvent()->getQuote();
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        if ($quote){
            $importOrder = $quote->getData(\Riki\CsvOrderMultiple\Cron\ImportOrders::CSV_ORDER_IMPORT_FLAG);
            // in case of import order, log if this observer makes the payment method becomes unavailable.
            $doLog = $result->getData('is_available') && $importOrder;
        }

        if ($quote) {
            $currentCustomerGroup = $quote->getData('customer_group_id');

            //get the customer list of current payment method
            $dataGroups = $this->_helperData->toArrayCustomerGroup($this->_helperData->getCustomerGroup($paymentMethod));

            //check the customer is exist in the customer groups of this payment method.
            if (!in_array($currentCustomerGroup, $dataGroups) && $paymentMethod != 'free') {
                $result->setData('is_available', false);
            }

            $courseId = $quote->getData('riki_course_id');
            // Subscription Course checkout
            if ($courseId) {
                //get the payment list of current course
                $courseFactory = $this->_courseFactory->create()->load($courseId);
                $allowPayments = $courseFactory->getAllowPaymentMethod();
                // set paygent method for delay payment
                if ($courseFactory->getIsDelayPayment()) {
                    if (in_array(\Bluecom\Paygent\Model\Paygent::CODE, $allowPayments)) {
                        $allowPayments = [
                            0 => \Bluecom\Paygent\Model\Paygent::CODE
                        ];
                    } else {
                        $allowPayments = [];
                    }
                }
                //check the current payment is exist in the payment list
                if ($paymentMethod != 'free' && !in_array($paymentMethod, $allowPayments)) {
                    $result->setData('is_available', false);
                }
            }
            if ($quote->getData('new_shipping_address') and $paymentMethod == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_COD) {
                $result->setData('is_available', false);
            }
        }

        if (isset($doLog) && $doLog && !$result->getData('is_available')) {
            $this->csvOrderLogger->info(__(
                    'original_unique_id [%1]: Payment method is disable by observer %2',
                    $quote->getOriginalUniqueId(),
                    self::class)
            );
        }
    }
}
