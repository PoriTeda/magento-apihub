<?php
namespace Riki\AdvancedInventory\Plugin\Quote\Model\QuoteManagement;

use Riki\Subscription\Model\Emulator\Config;
use Riki\AdvancedInventory\Api\ConfigInterface;

class OutOfStockEmail
{
    /**
     * @var bool
     */
    protected $isEnabled;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\AdvancedInventory\Observer\OutOfStockCapture
     */
    protected $captureObserver;

    /**
     * @var \Riki\Framework\Helper\ScopeConfig
     */
    protected $scopeConfigHelper;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * OutOfStockEmail constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Observer\OutOfStockCapture $captureObserver
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Riki\Framework\Helper\ScopeConfig $scopeConfigHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Observer\OutOfStockCapture $captureObserver
    ) {
        $this->scopeHelper = $scopeHelper;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfigHelper = $scopeConfigHelper;
        $this->logger = $logger;
        $this->captureObserver = $captureObserver;

        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->setIsEnabled(true);
    }

    /**
     * Get isEnabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isEnabled
     *
     * @param $isEnabled
     *
     * @return bool
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
        return $this->isEnabled;
    }

    /**
     * Send out of stock email
     *
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param \Magento\Sales\Model\Order $order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function afterSubmit(
        \Magento\Quote\Model\QuoteManagement $subject,
        $order
    ) {
        if (!$this->getIsEnabled()) {
            return $order;
        }

        if (!$order instanceof \Magento\Sales\Model\Order) {
            return $order;
        }

        $scope = \Riki\AdvancedInventory\Model\Queue\OosConsumer::class . '::execute';
        if ($this->scopeHelper->isInFunction($scope)) {
            return $order;
        }

        if ($order->getResource()->getMainTable() == Config::getOrderTmpTableName()) {
            // no apply for simulate subscription order
            return $order;
        }

        $outOfStocks = $this->captureObserver->getOutOfStocks($order->getQuoteId());
        if (!$outOfStocks) {
            return $order;
        }

        $this->order = $order;

        $groups = [
            'salesRule' => []
        ];
        /** @var \Riki\AdvancedInventory\Model\OutOfStock $outOfStock */
        foreach ($outOfStocks as $outOfStock){
            if ($outOfStock->getSalesruleId()) {
                $groups['salesRule'][] = $outOfStock;
            }
        }
        $this->sendEmailSalesRule($groups['salesRule']);

        return $order;
    }

    /**
     * Send notification email
     *
     * @param array $outOfStocks
     *
     * @return bool
     */
    public function sendEmailSalesRule($outOfStocks = [])
    {
        if (!$outOfStocks) {
            return false;
        }

        $template = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->outOfStock()
            ->freeGift()
            ->emailTemplate();
        if (!$template) {
            return false;
        }

        $recipients = $this->scopeConfigHelper->read(ConfigInterface::class)
            ->outOfStock()
            ->freeGift()
            ->emailRecipients();
        $recipients = trim($recipients);
        if (!$recipients) {
            return false;
        }

        $recipients = array_filter(explode(',', $recipients), 'trim');

        $this->inlineTranslation->suspend();
        try {
            $vars = [
                'order' => $this->order,
                'outOfStocks' => $outOfStocks,
            ];
            $this->transportBuilder
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateIdentifier($template)
                ->setTemplateVars($vars)
                ->addTo($recipients)
                ->getTransport()
                ->sendMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        $this->inlineTranslation->resume();

        return true;
    }
}