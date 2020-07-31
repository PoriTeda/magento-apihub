<?php
namespace Riki\EmailMarketing\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Email
 * @package Riki\EmailMarketing\Helper
 */
class Email extends AbstractHelper
{
    /**
     * @var
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

    /**
     * Email constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation

    ) {
        $this->_scopeConfig = $context;
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_inlineTranslation = $inlineTranslation;
        parent::__construct($context);
    }
    /**
     * @return mixed
     */
    public function getErrorReceiver()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('system/error_cron/error_cron_email',$storeScope);
    }
    /**
     * @return mixed
     */
    public function getSenderEmail()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/email',$storeScope);
    }
    /**
     * @return mixed
     */
    public function getSenderName()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('trans_email/ident_support/name',$storeScope);
    }

    /**
     * @return mixed
     */
    public function getEmailTemplate(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue('system/error_cron/error_cron_template',$storeScope);

    }
    /**
     * @param $emailTemplateVariables
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $senderInfo = [
            'name' => $this->getSenderName() , 'email' => $this->getSenderEmail()
        ];

        $this->_transportBuilder->setTemplateIdentifier($this->getEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($senderInfo)
            ->addTo($this->getErrorReceiver());
        return $this;
    }

    /**
     * @param $emailTemplateVariables
     */
    public function sendErrorlEmail($emailTemplateVariables)
    {
        $this->_inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->_inlineTranslation->resume();
    }


}