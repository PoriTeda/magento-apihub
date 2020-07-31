<?php
namespace Riki\Framework\Model\Email;

abstract class AbstractEmail
{
    const CONFIG_SENDER = '';
    const CONFIG_RECEIVER = '';
    const CONFIG_TEMPLATE = '';

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $sender;

    /**
     * @var string[]
     */
    protected $receiver = [];

    /**
     * @var string
     */
    protected $area;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $variables;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * AbstractEmail constructor.
     *
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->variables = $dataObjectFactory->create();
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Get email template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->template && static::CONFIG_TEMPLATE) {
            $this->template = $this->scopeConfig->getValue(static::CONFIG_TEMPLATE);
        }

        return $this->template;
    }

    /**
     * Set email template
     *
     * @param $template
     *
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Get sender
     *
     * @return string
     */
    public function getSender()
    {
        if (!$this->sender && static::CONFIG_SENDER) {
            $this->sender = $this->scopeConfig->getValue(static::CONFIG_SENDER);
        }

        return $this->sender;
    }

    /**
     * Set sender
     *
     * @param $sender
     *
     * @return $this
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Get recipients
     *
     * @return array
     */
    public function getReceiver()
    {
        if (!$this->receiver && static::CONFIG_RECEIVER) {
            $receiver = $this->scopeConfig->getValue(static::CONFIG_RECEIVER);
            if (strpos($receiver, ',') !== false) {
                $receiver = explode(',', $receiver);
            } elseif (strpos($receiver, ';') !== false) {
                $receiver = explode(';', $receiver);
            } else {
                $receiver = $receiver ? [$receiver] : [];
            }
            $this->receiver = array_map('trim', $receiver);
        }

        return $this->receiver;
    }

    /**
     * Set receiver
     *
     * @param $receiver
     *
     * @return $this
     */
    public function setReceiver($receiver)
    {
        if (!is_array($receiver)) {
            $receiver = [$receiver];
            array_push($this->receiver, ...$receiver);
        }

        return $this;
    }

    /**
     * Get area
     *
     * @return string
     */
    public function getArea()
    {
        if (!$this->area) {
            $this->setArea(\Magento\Framework\App\Area::AREA_FRONTEND);
        }

        return $this->area;
    }

    /**
     * Set area
     *
     * @param $area
     *
     * @return $this
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get store id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (!$this->storeId) {
            $this->setStoreId($this->storeManager->getStore()->getId());
        }
        return $this->storeId;
    }

    /**
     * Set store id
     *
     * @param $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;

        return $this;
    }

    /**
     * Get data
     *
     * @return \Magento\Framework\DataObject
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Send
     *
     * @param $params
     *
     * @return bool
     */
    public function send($params = [])
    {
        $params = array_merge($params, $this->getVariables()->getData());

        $receiver = isset($params['receiver']) ? (array)$params['receiver'] : $this->getReceiver();
        $sender = isset($params['sender']) ? $params['sender'] : $this->getSender();
        $template = isset($params['template']) ? $params['template'] : $this->getTemplate();

        if (!$receiver) {
            return true;
        }

        if (!$template) {
            return true;
        }

        try {
            $this->inlineTranslation->suspend();
            $this->transportBuilder
                ->setTemplateOptions([
                    'area' => $this->getArea(),
                    'store' => $this->getStoreId(),
                ])
                ->setTemplateIdentifier($template)
                ->setTemplateVars($params)
                ->addTo($receiver);
            if ($sender) {
                $this->transportBuilder->setFrom($sender);
            }
            $this->transportBuilder->getTransport()->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }

        return true;
    }
}
