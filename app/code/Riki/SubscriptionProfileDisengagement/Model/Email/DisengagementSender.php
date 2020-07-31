<?php
namespace Riki\SubscriptionProfileDisengagement\Model\Email;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;

class DisengagementSender
{
    const XML_PATH_EMAIL_IDENTITY = 'subscriptioncourse/profile_disengagement_email/identity';
    const XML_PATH_EMAIL_TEMPLATE = 'subscriptioncourse/profile_disengagement_email/template';
    const XML_PATH_EMAIL_COPY_TO = 'subscriptioncourse/profile_disengagement_email/copy_to';

    protected $_scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    protected $logger;

    protected $storeManager;

    protected $_currentProfile;

    protected $_profileHelperData;

    protected $_simulatorHelper;

    protected $_date;

    protected $_orderHelper;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var Renderer
     */
    protected $addressRenderer;

    public function __construct(
        TransportBuilder $transportBuilder,
        \Riki\Subscription\Logger\Logger $logger,
        StoreManagerInterface $storeManager,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Riki\Subscription\Helper\Order\Simulator $simulatorHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\EmailMarketing\Helper\Order $orderHelper
    ){

        $this->_scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->_profileHelperData = $profileHelper;
        $this->_date = $date;
        $this->_simulatorHelper = $simulatorHelper;
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->_orderHelper = $orderHelper;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return $this
     */
    public function setProfile(\Riki\Subscription\Model\Profile\Profile $profile){
        $this->_currentProfile = $profile;
        return $this;
    }

    /**
     * @return \Riki\Subscription\Model\Profile\Profile
     */
    public function getProfile(){
        return $this->_currentProfile;
    }

    /**
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send(){
        $profile = $this->getProfile();
        if(is_null($profile))
            return false;
        $customer = $profile->getCustomer();
        if(is_null($customer))
            return false;
        $templateId = $this->_getConfig(self::XML_PATH_EMAIL_TEMPLATE);
        $toName = $customer->getLastname() . ' ' . $customer->getFirstname();
        $toEmail = $customer->getEmail();
        $copyTo = $this->_getConfig(self::XML_PATH_EMAIL_COPY_TO);
        $identity = $this->_getConfig(self::XML_PATH_EMAIL_IDENTITY);
        $vars['customer_name'] =  $toName;
        $vars['course_name']    =  $profile->getCourseName();
        $vars['cancellation_date']    =  $this->_date->date('Y-m-d');
        try {
            $this->_send(
                $toEmail,
                $toName,
                empty($copyTo)? [] : explode(',', $copyTo),
                $templateId,
                $vars,
                $identity
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return true;
    }

    /**
     * @param $email
     * @param $name
     * @param $copyTo
     * @param $templateId
     * @param $templateVars
     * @param $emailIdentity
     */
    protected function _send($email, $name, $copyTo, $templateId, $templateVars, $emailIdentity)
    {
        $this->transportBuilder->setTemplateIdentifier($templateId);
        $this->transportBuilder->setTemplateVars($templateVars);
        $this->transportBuilder->setTemplateOptions($this->getTemplateOptions());
        $this->transportBuilder->setFrom($emailIdentity);

        $this->transportBuilder->addTo(
            $email,
            $name
        );

        if (!empty($copyTo)) {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    /**
     * @return array
     */
    protected function getTemplateOptions()
    {
        return [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $this->getStore()->getStoreId()
        ];
    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function _getConfig($path){
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $address
     * @return null|string
     */
    protected function getFormattedAddress($address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

    /**
     * Get payment info block as html
     *
     * @param Order $order
     * @return string
     */
    protected function getPaymentHtml(Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->getStore()->getStoreId()
        );
    }
}
