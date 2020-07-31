<?php
namespace Riki\Chirashi\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_fullChirashiShipments = [];
    protected $_haveOnlyCaseChirashiShipments = [];

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return bool
     */
    public function isFullChirashiItemShipment(\Magento\Sales\Model\Order\Shipment $shipment){

        if(!isset($this->_fullChirashiShipments[$shipment->getId()])){
            $isFullChirashi = true;

            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            foreach($shipment->getAllItems() as $item){
                if(!$item->getChirashi()){
                    $isFullChirashi = false;
                    break;
                }
            }

            $this->_fullChirashiShipments[$shipment->getId()] = $isFullChirashi;
        }

        return $this->_fullChirashiShipments[$shipment->getId()];
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return mixed
     */
    public function haveOnlyCaseAndChirashiShipment(\Magento\Sales\Model\Order\Shipment $shipment){
        if(!isset($this->_haveOnlyCaseChirashiShipments[$shipment->getId()])){

            $hasCase = false;
            $hasNonCase = false;
            $hasChirashi = false;
            $hasNonChirashi = false;

            /** @var \Magento\Sales\Model\Order\Shipment\Item $item */
            foreach($shipment->getAllItems() as $item){
                if($item->getChirashi()){
                    $hasChirashi = true;
                }else{
                    $hasNonChirashi = true;
                }

                if($item->getUnitCase() == 'CS'){
                    $hasCase = true;
                }else{
                    $hasNonCase = true;
                }
            }

            $this->_haveOnlyCaseChirashiShipments[$shipment->getId()] = $hasCase && !$hasNonCase && $hasChirashi && !$hasNonChirashi;
        }

        return $this->_haveOnlyCaseChirashiShipments[$shipment->getId()];
    }
}
