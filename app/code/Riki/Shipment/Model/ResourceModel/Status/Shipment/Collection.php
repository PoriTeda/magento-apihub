<?php
/**
 * Payment Status
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\ResourceModel\Status\Shipment;
use Magento\Sales\Model\Order\Shipment;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment
    as ShipmentStatusOptions;
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
/**
 * Class Collection
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Collection extends AbstractCollection
{
    /**
     * Define variables
     *
     * @var string
     */
    protected $_idFieldName = 'shipment_status_id';

    protected $_salesShipment;

    protected $_timezone;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    )
    {
        $this->_timezone = $timezone;
        parent::__construct
        (
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Shipment\Model\Status\Shipment',
            'Riki\Shipment\Model\ResourceModel\Status\Shipment'
        );
        $this->_map['fields']['shipment_status_id'] = 'main_table.shipment_status_id';
    }

    /**
     * Set sales shipment
     *
     * @param $shipment
     *
     * @return $this
     */
    public function setSalesShipment($shipment)
    {
        $this->_salesShipment = $shipment;
        return $this;
    }

    /**
     * Get sales shipment
     *
     * @return Shipment
     */
    public function getSalesShipment()
    {
        return $this->_salesShipment;
    }

    /**
     * Filter shipment
     *
     * @param $shipment
     * @return $this
     */
    public function setShipmentFilter($shipment)
    {
        if ($shipment instanceof Shipment) {
            $this->setSalesShipment($shipment);
            $shipmentId = $shipment->getId();
            if ($shipmentId) {
                $this->addFieldToFilter('shipment_id', $shipmentId);
            } else {
                $this->_totalRecords = 0;
                $this->_setIsLoaded(true);
            }
        } else {
            $this->addFieldToFilter('shipment_id', $shipment);
        }
        return $this;
    }

    /**
     * Get Shipment
     *
     * @param string $statusCode
     * @return \Riki\Shipment\Model\Status\Shipment
     */
    protected function _getStatusByShipmentStatus($statusCode)
    {
        foreach ($this as $status) {
            /* @var $status \Riki\Shipment\Model\Status\Shipment */
            if ($status->getShipmentStatus() == $statusCode) {
                return $status;
            }
        }
    }

    /**
     * Format date
     *
     * @param $date
     * @return string
     */
    protected function _formatDate($date)
    {
        return $this->_timezone->formatDateTime(
            new \DateTime($date),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
            null,
            $this->_timezone->getConfigTimezone('store', $this->getSalesShipment()->getStore())
        );
    }

    /**
     * Get Shipped out date
     *
     * @return string
     */
    public function getShippedOutDate()
    {
        if($this->_salesShipment->getShippedOutDate())
        {
            return $this->_formatDate($this->_salesShipment->getShippedOutDate());
        }
        else
        {
            return '';
        }
    }

    /**
     * Get delivery completion date
     *
     * @return string
     */
    public function getDeliveryCompletionDate()
    {
        if($this->_salesShipment->getDeliveryCompleteDate())
        {
            return $this->_formatDate($this->_salesShipment->getDeliveryCompleteDate());
        }
        else
        {
            return '';
        }
    }
}