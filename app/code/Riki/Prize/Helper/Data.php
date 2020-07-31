<?php
namespace Riki\Prize\Helper;
use Magento\Framework\App\Helper\Context;
class Data  extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_ENABLE ='prize/prizeoutofstock/enable';
    const CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_SENDER ='prize/prizeoutofstock/sender';
    const CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_TO ='prize/prizeoutofstock/to';
    const CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_TEMPLATE ='prize/prizeoutofstock/email_template';

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslate;

    /**enable
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

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

    public function sendEmailPrizeProductOutOfStock($emailTemplateVariables)
    {
        try {
            $this->_inlineTranslate->suspend();
            $this->generateTemplate($emailTemplateVariables);
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
            $this->_inlineTranslate->resume();
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
    }
    /**
     * @param $emailTemplateVariables
     * @param $emailReceiver
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $emailSender = $this->getConfig(self::CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_TO);
        $template =  $this->_transportBuilder->setTemplateIdentifier($this->getPrizeEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and                                                                                 store of template for which you prepare it */
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($this->getConfig(self::CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_SENDER))
            ->addTo($emailSender);
        return $this;
    }
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }
    public function getPrizeEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_PRIZE_PRODUCT_OUT_OF_STOCK_EMAIL_TEMPLATE, $storeScope);
        return $template;
    }

}