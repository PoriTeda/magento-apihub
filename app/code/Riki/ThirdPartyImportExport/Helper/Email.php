<?php
namespace Riki\ThirdPartyImportExport\Helper;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $_inlineTranslation;

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
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Set sender
     *
     * @param $name
     * @param $sender
     * @return $this
     */
    public function setFrom($name, $sender)
    {
        $this->_transportBuilder->setFrom([
            'name' => $name,
            'email' => $sender
        ]);
        return $this;
    }

    /**
     * Set receiver
     *
     * @param array $receivers
     * @return $this
     */
    public function setTo($receivers = [])
    {
        $this->_transportBuilder->addTo($receivers);
        return $this;
    }

    /**
     * Set body
     *
     * @param $template
     * @param $vars
     * @return $this
     */
    public function setBody($template, $vars)
    {
        $this->_transportBuilder
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->_storeManager->getStore()->getId(),])
            ->setTemplateIdentifier($template)
            ->setTemplateVars($vars);
        return $this;
    }

    /**
     * Send email
     *
     * @return $this
     */
    public function send()
    {
        $this->_inlineTranslation->suspend();
        try {
            $this->_transportBuilder
                ->getTransport()
                ->sendMessage();
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
        $this->_inlineTranslation->resume();

        return $this;
    }
}
