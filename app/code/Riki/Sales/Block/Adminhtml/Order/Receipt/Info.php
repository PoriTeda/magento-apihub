<?php
namespace Riki\Sales\Block\Adminhtml\Order\Receipt;

use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Store\Model\ScopeInterface;

/**
 * Order history block
 * Class Info
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Riki\Shipment\Helper\ShipmentHistory
     */
    protected $shipmentHistory;

    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Riki\Tax\Helper\Data
     */
    protected $taxHelper;

    /**
     * Info constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Shipment\Helper\ShipmentHistory $shipmentHistory
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Magento\Directory\Model\Currency $currency
     * @param \Riki\Tax\Helper\Data $taxHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Shipment\Helper\ShipmentHistory $shipmentHistory,
        \Riki\Sales\Helper\Order $orderHelper,
        \Magento\Directory\Model\Currency $currency,
        \Riki\Tax\Helper\Data $taxHelper,
        array $data = []
    ) {
        $this->shipmentHistory = $shipmentHistory;
        $this->orderHelper = $orderHelper;
        $this->config = $context->getScopeConfig();
        $this->currency = $currency;
        $this->coreRegistry = $registry;
        $this->taxHelper = $taxHelper;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Retrieve available order
     *
     * @return Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->getData('order');
        }
        if ($this->coreRegistry->registry('current_order')) {
            return $this->coreRegistry->registry('current_order');
        }
        if ($this->coreRegistry->registry('order')) {
            return $this->coreRegistry->registry('order');
        }
        throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t get the order instance right now.'));
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getHistoryShipment(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        return $this->shipmentHistory->getShipmentDateHistory($shipment);
    }

    /**
     * @return \Magento\Framework\Phrase|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getReceiptName()
    {
        $receiptNumber = $this->_request->getParam('print-order-name', 1);
        return $this->orderHelper->getReceiptName($this->getOrder(), $receiptNumber);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShippedOutDate()
    {
        return $this->orderHelper->getOrderShippedOutDate($this->getOrder());
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentTitle()
    {
        $paymentMethod = $this->getOrder()->getPayment()->getMethod();
        $configPath = 'payment/'.$paymentMethod.'/title';
        return $this->config->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getCurrencySymbol()
    {
        return __('currency symbol english');
    }

    /**
     * @param float $value
     * @return string
     */
    public function formatCurrencyInvoice($value)
    {
        $symbol = $this->getCurrencySymbol();
        if (is_object($value)) {
            $formatNumber = $value->getValue();
        } else {
            $formatNumber = $value;
        }
        return $symbol.number_format((int)$formatNumber, 0, '', ',');
    }

    /**
     * Get qty from order item
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int|null
     */
    public function getQtyOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        $qty = (int)$item->getQtyOrdered();

        if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $unitQty = (null !== $item->getUnitQty()) ? $item->getUnitQty() : 1;
            $qty = $qty / $unitQty;
        }
        return $qty;
    }

    /**
     * Get unit qty
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @return int|null
     */
    public function getItemUnitQty(\Magento\Sales\Model\Order\Item $item)
    {
        $unitQty = 1;
        if ($item->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $unitQty = (null !== $item->getUnitQty()) ? $item->getUnitQty() : 1;
        }
        return $unitQty;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentTotalOrder()
    {
        $order = $this->getOrder();
        return $this->orderHelper->getOrderTotals($order);
    }

    /**
     * @param int $orderId
     * @return int
     */
    public function getOrderReceiptCounter(int $orderId)
    {
        return $this->orderHelper->retreiveReceiptCounter($orderId);
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function canApplyTaxChangeFromDate($orderId)
    {
        return $this->taxHelper->canApplyTaxChangeFromDate($orderId);
    }

    /**
     * Get config tax percent to compare to current product tax
     * @return int
     */
    public function getCompareTaxPercent()
    {
        return $this->taxHelper->getCompareTaxPercent();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return float
     */
    public function getRowsTotal(\Magento\Sales\Model\Order $order)
    {
        return $this->orderHelper->getRowsTotal($order);
    }
}
