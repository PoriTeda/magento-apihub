<?php
namespace Riki\ShippingCarrier\Block\Adminhtml\View\Address;

use Riki\CvsPayment\Api\ConstantInterface;

class Shipping extends \Magento\Sales\Block\Adminhtml\Order\View\Info
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $salesAddressHelper;

    /**
     * @var \Riki\Shipment\Model\Status\Shipment
     */
    protected $statusHistory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Shipping constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadata
     * @param \Magento\Customer\Model\Metadata\ElementFactory $elementFactory
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Riki\Sales\Helper\Address $addressHelper
     * @param \Riki\Shipment\Model\Status\Shipment $statusHistory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Riki\Sales\Helper\Address $addressHelper,
        \Riki\Shipment\Model\Status\Shipment $statusHistory,
        array $data = []

    ) {
        $this->_coreRegistry = $registry;
        $this->salesAddressHelper = $addressHelper;
        $this->statusHistory = $statusHistory;
        $this->timezone = $context->getLocaleDate();
        $this->scopeConfig = $context->getScopeConfig();

        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $groupRepository,
            $metadata,
            $elementFactory,
            $addressRenderer,
            $data
        );
    }

    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->_coreRegistry->registry('current_shipment');
    }

    /**
     * @return $this|bool
     */
    public function getShipmentAddress()
    {
        $shipmentItems = $this->getShipment()->getItemsCollection();
        $firstOrderItemId = $shipmentItems[array_keys($shipmentItems)[0]]->getOrderItemId();
        return $this->salesAddressHelper->getOrderAddressByOrderItem($firstOrderItemId);
    }

    /**
     * Get shipped out date
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getShippedOutDate()
    {
        $shipment = $this->getShipment();
        return $shipment->getShippedOutDate();
    }

    /**
     * Get Delivery Completed date
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDeliveryCompletionDate()
    {
        $shipment = $this->getShipment();
        return $shipment->getDeliveryCompleteDate();
    }

    /**
     * Format date
     *
     * @param mixed $date
     * @return string
     */
    protected function _formatDate($date)
    {
        return $this->timezone->formatDateTime(
            new \DateTime($date),
            \IntlDateFormatter::MEDIUM,
            \IntlDateFormatter::NONE,
            null,
            $this->timezone->getConfigTimezone('store', $this->getShipment()->getStore())
        );
    }

    /**
     * @param \Riki\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderCvsCreateByCommand($order)
    {
        if ($order->getPayment()->getMethod() == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
            $valueConfig = $this->scopeConfig->getValue(
                ConstantInterface::CONFIG_PATH_COMMAND_CREATE_ORDER_CVS_PAYMENT_SKU
            );
            $productSkus = ($valueConfig) ? array_map('trim', explode(';', strtolower($valueConfig))) : null;
            if (!empty($productSkus)) {
                foreach ($order->getItems() as $item) {
                    if (in_array(strtolower($item->getSKu()), $productSkus)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param \Riki\Sales\Model\Order $order
     * @param \Riki\Sales\Model\Order\Address $address
     * @return null|string
     */
    public function getFormattedShippingAddress($order, $address)
    {
        if ($this->isOrderCvsCreateByCommand($order)) {
            return null;
        }

        return $this->getFormattedAddress($address);
    }
}
