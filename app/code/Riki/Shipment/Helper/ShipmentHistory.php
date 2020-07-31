<?php
/**
 * Shipment History
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Helper\Context;
use Riki\Shipment\Model\ResourceModel\Status\Shipment\CollectionFactory;
use Riki\Shipment\Model\Status\ShipmentFactory;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
/**
 * Class ShipmentHistory
 *
 * @category  RIKI
 * @package   Riki\Shipment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce

 */
class ShipmentHistory extends AbstractHelper
{
    /**
     * @var TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var CollectionFactory
     */
    protected $shipmentHistoryCollection;
    /**
     * @var ShipmentFactory
     */
    protected $shipmentHistoryModel;

    /* @var \Magento\Sales\Api\OrderRepositoryInterface */
    protected $orderRepositoryInterface;

    /* @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $_searchCriteriaBuilder;

    /* @var \Magento\Framework\Api\SortOrder */
    protected $sortOrder;

    /**
     * ShipmentHistory constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        SortOrder $sortOrder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepositoryInterface,
        Context $context,
        TimezoneInterface $timeZone,
        DateTime $dateTime,
        CollectionFactory $shipmentCollection,
        ShipmentFactory $shipmentFactory

    ) {
        parent::__construct($context);
        $this->sortOrder = $sortOrder;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->dateTime = $dateTime;
        $this->timeZone = $timeZone;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipmentHistoryCollection = $shipmentCollection;
        $this->shipmentHistoryModel = $shipmentFactory;

    }

    /**
     * @param $data
     * @throws \Exception
     */
    public function addShipmentHistory($data)
    {
        $originDate =  $this->timeZone->formatDateTime($this->dateTime->gmtDate(),2);
        $needDate = $this->dateTime->gmtDate('Y-m-d H:i:s',$originDate);
        if(!array_key_exists('shipment_date', $data))
        {
            $data['shipment_date'] = $needDate;
        }
        if(!array_key_exists('shipment_id', $data))
        {
            $data['shipment_id'] = 0;
        }
        $collection = $this->shipmentHistoryCollection->create()
            ->setPageSize(1)
            ->setCurPage(1);
        if(array_key_exists('shipment_status', $data))
        {
            $collection->addFieldToFilter('shipment_status', $data['shipment_status']);
        }
        if(array_key_exists('shipment_id', $data))
        {
            $collection->addFieldToFilter('shipment_id', $data['shipment_id']);
        }
        if($collection->getSize())
        {
            try{
                $shipmentObject =  $collection->getFirstItem();
                $shipmentObject->setShipmentDate($data['shipment_date'])->save();
            }catch(\Exception $e){
                throw $e;
            }

        }
        else
        {
            if($data['shipment_id'])
            {
                try{
                    $this->shipmentHistoryModel->create()->setData($data)->save();
                }catch(\Exception $e){
                    throw $e;
                }

            }
        }
    }//end function

    /**
     * @param $dateString
     * @return string
     */
    public function convertDate($dateString)
    {
        $year = substr($dateString,0,4);
        $month = substr($dateString,4,2);
        $day = substr($dateString,6,2);
        return $year.'-'.$month.'-'.$day;
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getShipmentDateHistory
    (
        \Magento\Sales\Model\Order\Shipment $shipment
    )
    {
        $dates = [];
        $dates['created'] = $this->dateTime->gmtDate('Y/m/d',$shipment->getCreatedAt());
        if($shipment->getDeliveryDate())
        {
            $dates['delivery_date']=
                $this->dateTime->gmtDate('Y/m/d',$shipment->getDeliveryDate());
        }
        else
        {
            $dates['delivery_date']= '';
        }
        $collection = $this->shipmentHistoryCollection->create();
        $collection->addFieldToFilter('shipment_id', $shipment->getId());
        $status = [
            ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
            ShipmentStatus::SHIPMENT_STATUS_EXPORTED,
            ShipmentStatus::SHIPMENT_STATUS_SHIPPED_OUT,
            ShipmentStatus::SHIPMENT_STATUS_REJECTED
        ];
        if($collection->getSize())
        {
            foreach($collection as $shipDate)
            {
                foreach($status as $shipStatus)
                {
                    if($shipDate->getShipmentStatus()==$shipStatus)
                    {
                        if($shipDate->getShipmentDate())
                        {
                            $dates[$shipStatus]= $this->dateTime->gmtDate('Y/m/d',$shipDate->getShipmentDate());
                        }
                        else
                        {
                            $dates[$shipStatus] = '';
                        }
                    }
                }
            }
        }
        foreach($status as $state)
        {
            if(!array_key_exists($state,$dates))
            {
                $dates[$state] = '';
            }
        }
        return $dates;
    }

    /**
     * Calculate Nth of order subscription
     *
     * @param $order
     *
     * @return int
     */
    public function calculateNthOrder($order)
    {
        return $order->getSubscriptionOrderTime();
    }

}//end class