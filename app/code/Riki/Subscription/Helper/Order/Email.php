<?php
namespace Riki\Subscription\Helper\Order;
use Magento\Framework\App\Helper\Context;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_CHANGE_PROFILE_EMAIL_ENABLE = 'subscriptioncourse/subscriptionprofileedit/enable';
    const CONFIG_CHANGE_PROFILE_EMAIL_TEMPLATE = 'subscriptioncourse/subscriptionprofileedit/email_template';
    const CONFIG_CHANGE_PROFILE_EMAIL_SENDER = 'subscriptioncourse/subscriptionprofileedit/sender';
    const CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_METHOD = 'subscriptioncourse/subscriptionprofileedit/send_email_copy_method';
    const CONFIG_CHANGE_PROFILE_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/subscriptionprofileedit/send_email_copy_to';

    const CONFIG_REPLACE_PRODUCT_EMAIL_TEMPLATE = 'subscriptioncourse/replaceproduct/email_template';
    const CONFIG_REPLACE_PRODUCT_EMAIL_SENDER = 'subscriptioncourse/replaceproduct/sender';
    const CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_METHOD = 'subscriptioncourse/replaceproduct/send_email_copy_method';
    const CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/replaceproduct/send_email_copy_to';

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

    public function sendEmailNotification($emailTemplateVariables, $emailTemplate, $area, $from, $to, $copyTo = null, $copyMethod = null)
    {
        try {
            $this->_inlineTranslate->suspend();
            $this->generateTemplate($emailTemplateVariables, $emailTemplate, $area, $from, $to, $copyTo, $copyMethod);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslate->resume();
            return true;
        } catch(\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * @param $emailTemplateVariables
     * @param $emailTemplate $this->getConfig(self::CONFIG_REPLACE_PRODUCT_EMAIL_TEMPLATE)
     * @param $area \Magento\Framework\App\Area::AREA_ADMINHTML
     * @param $from self::CONFIG_REPLACE_PRODUCT_EMAIL_SENDER
     * @param $copyTo self::CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_TO
     * @copyMethod self::CONFIG_REPLACE_PRODUCT_EMAIL_SEND_EMAIL_COPY_METHOD
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables,$emailTemplate,$area,$from,$to,$copyTo=null,$copyMethod=null)
    {
        $to = explode(';',$to);
        $this->_transportBuilder->setTemplateIdentifier($emailTemplate)
            ->setTemplateOptions(
                [
                    'area' => $area, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($from)
            ->addTo($to);
        if($copyTo) {
            $copyTo = $this->getListEmail($copyTo);
            if (!empty($copyTo) && $this->getConfig($copyMethod) == 'bcc') {
                foreach ($copyTo as $email) {
                    $this->_transportBuilder->addBcc($email);
                }
            }
        }
        return $this;
    }
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }
    public function getListEmail($copyTo)
    {
        $listEmail = $this->getConfig($copyTo);
        if ($listEmail == null) {
            return array();
        }
        return explode(',',trim($listEmail));
    }
}