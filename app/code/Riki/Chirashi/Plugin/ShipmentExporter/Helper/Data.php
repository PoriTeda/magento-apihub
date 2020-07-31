<?php
namespace Riki\Chirashi\Plugin\ShipmentExporter\Helper;

class Data
{
    protected $_helper;

    protected $_logger;

    /**
     * @param \Riki\Chirashi\Helper\Data $helper
     */
    public function __construct(
        \Riki\Chirashi\Helper\Data $helper,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_helper = $helper;
        $this->_logger = $logger;
    }

    /**
     * @param \Riki\ShipmentExporter\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function aroundCanExport(
        \Riki\ShipmentExporter\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        if($this->_helper->isFullChirashiItemShipment($shipment)){
            return false;
        }
        else
            return $proceed($shipment);
    }

    /**
     * @param \Riki\ShipmentExporter\Helper\Data $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param \Magento\Sales\Model\Order\Shipment\Item $item
     * @return bool
     */
    public function aroundCanExportItem(
        \Riki\ShipmentExporter\Helper\Data $subject,
        \Closure $proceed,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Model\Order\Shipment\Item $item
    ) {
        $result = $proceed($shipment, $item);

        if($result){
            if($this->_helper->haveOnlyCaseAndChirashiShipment($shipment)){
                if($item->getChirashi())
                    $result = false;
            }
        }

        return $result;
    }

    /**
     * @param \Riki\ShipmentExporter\Helper\Data $subject
     * @param \Magento\Sales\Model\Order\Shipment $result
     * @return \Magento\Sales\Model\Order\Shipment
     * @throws \Exception
     */
    public function afterProcessUnableExportShipment(
        \Riki\ShipmentExporter\Helper\Data $subject,
        \Magento\Sales\Model\Order\Shipment $result
    ) {

        if($this->_helper->isFullChirashiItemShipment($result)){
            $result->setShipmentStatus(\Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_DELIVERY_COMPLETED);
            try{
                $result->save();
            }catch(\Exception $e){
                $this->_logger->error(__('Can not save shipment, message: %1', $e->getMessage()));
            }
        }

        return $result;
    }
}
