<?php
/**
 * Plugin after load shipment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\Plugin;
use \Riki\Shipment\Model\ResourceModel\Status\Shipment\CollectionFactory
    as ShipmentCollectionFactory;
use \Magento\Sales\Api\Data\ShipmentExtensionFactory
    as ShipmentExtensionFactory;

/**
 * Class AfterLoadShipment
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\Plugin
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class AfterLoadShipment
{
    protected $statusesCollectionFactory;

    /**
     * @var \Magento\Sales\Api\Data\ShipmentExtensionFactory
     */
    protected $shipmentExtensionFactory;

    /**
     * AfterLoadShipment constructor.
     * @param ShipmentCollectionFactory $statusesCollectionFactory
     * @param ShipmentExtensionFactory $shipmentExtensionFactory
     */
    public function __construct(
        ShipmentCollectionFactory $statusesCollectionFactory,
        ShipmentExtensionFactory $shipmentExtensionFactory
    )
    {
        $this->statusesCollectionFactory = $statusesCollectionFactory;
        $this->shipmentExtensionFactory = $shipmentExtensionFactory;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function afterAfterLoad(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $shipmentExtension = $shipment->getExtensionAttributes();
        if ($shipmentExtension === null) {
            $shipmentExtension = $this->shipmentExtensionFactory->create();
        }

//        $statusesCollection = $this->statusesCollectionFactory
//            ->create()
//            ->setShipmentFilter($shipment);
//
//        $shipmentExtension->setShippedOutDate
//        (
//            $statusesCollection->getShippedOutDate()
//        );
//        $shipmentExtension->setDeliveryCompletionDate
//        (
//            $statusesCollection->getDeliveryCompletionDate()
//        );

        $shipment->setExtensionAttributes($shipmentExtension);
        return $shipment;
    }
}