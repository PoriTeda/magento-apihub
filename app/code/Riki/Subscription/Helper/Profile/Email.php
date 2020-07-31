<?php

namespace Riki\Subscription\Helper\Profile;

use Magento\Framework\App\Helper\Context;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_CHANGE_PROFILE_EMAIL_ENABLE = 'subscriptioncourse/subscriptionprofileedit/enable';
    const CONFIG_CHANGE_PROFILE_EMAIL_TEMPLATE = 'subscriptioncourse/subscriptionprofileedit/email_template';
    const CONFIG_CHANGE_PROFILE_EMAIL_SENDER = 'subscriptioncourse/subscriptionprofileedit/sender';
    const CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_METHOD = 'subscriptioncourse/subscriptionprofileedit/send_email_copy_method';
    const CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/subscriptionprofileedit/send_email_copy_to';

    const CONFIG_REPLACE_PRODUCT_EMAIL_TEMPLATE = 'subscriptioncourse/replaceproduct/email_template';
    const CONFIG_DELETE_PRODUCT_EMAIL_TEMPLATE = 'subscriptioncourse/replaceproduct/delete_template';

    const CONFIG_REPLACE_PRODUCT_EMAIL_SENDER = 'subscriptioncourse/replaceproduct/sender';
    const CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_METHOD = 'subscriptioncourse/replaceproduct/send_email_copy_method';
    const CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/replaceproduct/send_email_copy_to';

    const XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDER = 'subscriptioncourse/profile_disengagement_email/identity';
    const XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_TEMPLATE_CONSUMER = 'subscriptioncourse/profile_disengagement_email/template';
    const XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_TEMPLATE_BUSINESS_USER = 'subscriptioncourse/profile_disengagement_email/template_to_business';
    const XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDTO_BUSINESS_USER = 'subscriptioncourse/profile_disengagement_email/business_user_email';
    const XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_COPY_TO = 'subscriptioncourse/profile_disengagement_email/copy_to';



    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslate;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Email constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Translate\Inline\StateInterface $translation
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Translate\Inline\StateInterface $translation
    )
    {
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslate = $translation;
        $this->_storeManager = $storeManagerInterface;
        parent::__construct($context);
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendEmailChangeProfile($emailTemplateVariables)
    {
        try {
            $this->_inlineTranslate->suspend();
            $this->generateTemplate($emailTemplateVariables);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslate->resume();
        } catch(\Exception $e) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objectManager->create('\Magento\Framework\Logger\Monolog')->addDebug($e->getMessage());
        }
    }

    /**
     * Send email notification for discontinued product
     *
     * @param array $emailTemplateVariables
     * @param bool $replace { true: replace product, false: delete product }
     */
    public function sendEmailNotificationDiscontinuedProduct($emailTemplateVariables, $replace = true)
    {
        try {
            $this->_inlineTranslate->suspend();
            $this->generateTemplateNotificationDiscontinuedProduct($emailTemplateVariables, $replace);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslate->resume();
        } catch(\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
    }

    /**
     * @param $emailTemplateVariables
     * @param $emailReceiver
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $emailSender = $emailTemplateVariables['emailReceiver'];

        $this->_transportBuilder->setTemplateIdentifier($this->getProfileEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($this->getConfig(self::CONFIG_CHANGE_PROFILE_EMAIL_SENDER))
            ->addTo($emailSender);
        $copyTo = $this->getListEmail();
        if (!empty($copyTo) && $this->getConfig(self::CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_METHOD) == 'bcc') {
            foreach ($copyTo as $email) {
                $this->_transportBuilder->addBcc($email);
            }
        }

        return $this;
    }

    /**
     * Generate email template for discontinued product
     *
     * @param array $emailTemplateVariables
     * @param bool $replace { true: replace template, false: delete template }
     * @return $this
     */
    public function generateTemplateNotificationDiscontinuedProduct($emailTemplateVariables, $replace = true)
    {
        $emailSender = $emailTemplateVariables['emailReceiver'];

        if ($replace) {
            $templateId = $this->getConfig(self::CONFIG_REPLACE_PRODUCT_EMAIL_TEMPLATE);
        } else {
            $templateId = $this->getConfig(self::CONFIG_DELETE_PRODUCT_EMAIL_TEMPLATE);
        }

        $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($this->getConfig(self::CONFIG_REPLACE_PRODUCT_EMAIL_SENDER))
            ->addTo($emailSender);

        $copyTo = $this->getListEmail(self::CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_TO);

        if (!empty($copyTo) && $this->getConfig(self::CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_METHOD) == 'bcc') {
            foreach ($copyTo as $email) {
                $this->_transportBuilder->addBcc($email);
            }
        }

        return $this;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * @param string $configEmails
     * @return array
     */
    public function getListEmail($configEmails = self::CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_TO)
    {
        $listEmail = $this->getConfig($configEmails);
        if ($listEmail == null) {
            return array();
        }
        return explode(',',trim($listEmail));
    }

    /**
     * @return mixed
     */
    public function getProfileEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_CHANGE_PROFILE_EMAIL_TEMPLATE, $storeScope);
        return $template;
    }

    /**
     * @return mixed
     */
    public function getBusinessDisengagementEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $path = self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_TEMPLATE_BUSINESS_USER;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * @return mixed
     */
    public function getDisengagementEmailSender()
    {
        return $this->scopeConfig->getValue(
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDER
        );
    }

    /**
     * @return mixed
     */
    public function getDisengagementEmailCopy()
    {
        return $this->scopeConfig->getValue(
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_COPY_TO
        );
    }

    /**
     * Get Sender name from configuration
     * @return mixed
     */
    public function getDisengagementSenderName()
    {
        $identify = 'ident_'.$this->getConfig(self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDER);
        $pathSenderName = 'trans_email/'.$identify.'/name';
        return $this->getConfig($pathSenderName);
    }

    /**
     * Get Sender email from configuration
     *
     * @return mixed
     */
    public function getDisengagementSenderEmail()
    {
        $identify = 'ident_'.$this->getConfig(self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDER);
        $pathSenderEmail = 'trans_email/'.$identify.'/email';
        return $this->getConfig($pathSenderEmail);
    }

    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    protected function generateBuisnessDisengagementTemplate($emailTemplateVariables)
    {
        $templateId = $this->getBusinessDisengagementEmailTemplate();
        $senderInfo = [
            'name' => $this->getDisengagementSenderName() ,
            'email' => $this->getDisengagementSenderEmail()
        ];
        $this->_transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo(
                $this->getListEmail(
                    self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_SENDTO_BUSINESS_USER
                )
            );

        $copyTo = $this->getListEmail(self::XML_CONFIG_PROFILE_DISENGAGEMENT_EMAIL_COPY_TO);
        if (!empty($copyTo)) {
            $this->_transportBuilder->addBcc($copyTo);
        }
        return $this;
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendDisengagementEmailToBusinessUser($emailTemplateVariables)
    {
        try {
            $this->_inlineTranslate->suspend();
            $this->generateBuisnessDisengagementTemplate($emailTemplateVariables);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslate->resume();
        } catch(\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}