<?php
namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Model\OutOfStock;

class SalesRule
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * SalesRule constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->scopeConfig = $scopeConfig;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->logger = $logger;
    }

    /**
     * Save wbs for SalesRule
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $subject
     * @param \Riki\AdvancedInventory\Model\OutOfStock $result
     *
     * @return \Riki\AdvancedInventory\Model\OutOfStock
     */
    public function afterAfterSave(
        \Riki\AdvancedInventory\Model\OutOfStock $subject,
        \Riki\AdvancedInventory\Model\OutOfStock $result
    ){
        if (!$result->dataHasChangedFor('generated_order_id')) {
            return $result;
        }

        if (!$result->getSalesruleId()) {
            return $result;
        }

        $this->processOrderItemName($result);

        return $result;
    }

    /**
     * Prefix order item name
     *
     * @param \Riki\AdvancedInventory\Model\OutOfStock $outOfStock
     */
    public function processOrderItemName(\Riki\AdvancedInventory\Model\OutOfStock $outOfStock)
    {
        $order = $this->outOfStockHelper->getGeneratedOrder($outOfStock);
        if (!$order) {
            return;
        }

        $prefix = $this->scopeConfig->getValue(
            'ampromo/messages/prefix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        foreach ($order->getItems() as $orderItem) {
            $orderItem->setName($prefix . ' ' . $orderItem->getName());
            try {
                $orderItem->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
                continue;
            }
        }
    }
}