<?php
namespace Riki\Subscription\Model\Email;

class ProfilePaymentMethodError extends \Riki\Framework\Model\Email\AbstractEmail
{
    const CONFIG_SENDER = 'subcreateorder/payment_method_error/sender';
    const CONFIG_TEMPLATE = 'subcreateorder/payment_method_error/email_template';

    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Url
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Framework\App\AreaList
     */
    protected $_areaList;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;
    /**
     * ProfilePaymentMethodError constructor.
     *
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Riki\Framework\Helper\Datetime $datetimeHelper
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\Url $urlBuilder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
        
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->priceCurrency = $priceCurrency;
        $this->datetimeHelper = $datetimeHelper;
        $this->_areaList = $areaList;
        $this->_state = $state;
        parent::__construct($dataObjectFactory, $storeManager, $scopeConfig, $logger, $inlineTranslation, $transportBuilder);
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && !parent::getTemplate()) {
            $this->template = 'subcreateorder_payment_method_error_email_template';
        }

        return $this->template;
    }

    /**
     * Send
     *
     * @param array $params
     *
     * @return bool
     */
    public function send($params = [])
    {
        $area = $this->_areaList->getArea($this->_state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
        $profile = isset($params['profile'])
            ? $params['profile']
            : $this->getVariables()->getData('profile');

        if ($profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            $params['profile_id'] = $profile->getId();
            $params['next_delivery_date'] = $profile->getData('next_delivery_date')
                ? $this->datetimeHelper->formatDate($profile->getData('next_delivery_date'), \IntlDateFormatter::MEDIUM)
                : '';
            $params['order_time'] = $profile->getData('order_times');
            $params['subscription_course_name'] = $profile->getCourseName();

            $courseData = $profile->getCourseData();
            $params['order_number_time'] =  isset($courseData['hanpukai_maximum_order_times'])
                ? (int)$courseData['hanpukai_maximum_order_times']
                : 0;
            $params['subscription_profile_page'] = $this->urlBuilder->getUrl('subscriptions/profile/payment_method_edit', [
                'id' => $profile->getId(),
                '_nosid' => true,
                '_scope' => $profile->getData('store_id')
            ]);
            $params['customer'] = $profile->getCustomer();
        }

        $customer = isset($params['customer'])
            ? $params['customer']
            : $this->getVariables()->getData('customer');
        if ($customer instanceof \Magento\Customer\Api\Data\CustomerInterface) {
            $params['customer_first_name'] = $customer->getFirstname();
            $params['customer_last_name'] = $customer->getLastname();
            $params['receiver'] = $customer->getEmail();
        }

        $quote = isset($params['quote'])
            ? $params['quote']
            : $this->getVariables()->getData('quote');
        if ($quote instanceof \Magento\Quote\Model\Quote) {
            if (!floatval($quote->getBaseGrandTotal())) {
                $quote->collectTotals();
            }
            $params['next_order_amount'] = $this->priceCurrency->format($quote->getBaseGrandTotal(),false);
            $billingInformation = [__('Billing Title')];
            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress instanceof \Magento\Quote\Model\Quote\Address) {
                $billingInformation[] = \Riki\EmailMarketing\Helper\Order::NEWLINE;
                $billingInformation[] = \Riki\EmailMarketing\Helper\Order::NEWLINE;
                $billingInformation[] =  sprintf(__('Billing Name %s %s'), $billingAddress->getLastname(), $billingAddress->getFirstname());
                $billingInformation[] = \Riki\EmailMarketing\Helper\Order::NEWLINE;
                $billingInformation[] = __('Billing Postcode:'). $billingAddress->getPostcode();
                $billingInformation[] = \Riki\EmailMarketing\Helper\Order::NEWLINE;
                $billingInformation[] = __($billingAddress->getRegion()) .' '
                    .$billingAddress->getStreetLine(1).' '
                    .$billingAddress->getData('apartment');
                $billingInformation[] = \Riki\EmailMarketing\Helper\Order::NEWLINE;
                $billingInformation[] = sprintf(__('Billing Telephone: %s'), $billingAddress->getTelephone());
            }
            $params['billing_information'] = implode('', $billingInformation);
        }

        return parent::send($params);
    }
}
