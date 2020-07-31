<?php
/**
 * Riki Sales calculate cut off date for Shipment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Sales\Helper;
/**
 * Class OrderStatus
 *
 * @category  RIKI
 * @package   Riki\Sales\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class OrderStatus extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var  \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Riki\Sales\Model\OrderPayshipStatus
     */
    protected $_orderPayshipModel;
    /**
     * @var \Riki\Sales\Model\ResourceModel\OrderPayshipStatus\Collection
     */
    protected $_orderPayshipCollection;
    /**
     * OrderStatus constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\Sales\Model\OrderPayshipStatus $orderPayshipStatus
     */

    /**
     * @var Magento\Sales\Model\ResourceModel\Order
     */
    protected $_orderResource;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Sales\Model\OrderPayshipStatus $orderPayshipStatus,
        \Riki\Sales\Model\ResourceModel\OrderPayshipStatus\Collection $orderPayShipCollection
    ) {
        $this->_orderPayshipModel = $orderPayshipStatus;
        $this->_orderPayshipCollection = $orderPayShipCollection;
        $this->_orderResource = $orderResource;
        $this->_dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * @param $data
     * @throws \Exception
     */
    public function addOrderPayShipStatus($data)
    {   //validate data
        if(!array_key_exists('status_date', $data)){
            $data['status_date'] = '';
        }
        if(!$data['status_date'] || date('Y-m-d',strtotime($data['status_date']))=='1970-01-01'){
            $data['status_date'] = date('Y-m-d H:i:s');
        }else{
            $data['status_date'] = date('Y-m-d H:i:s', strtotime($data['status_date']));
        }
        $collection = $this->_orderPayshipCollection
                    ->addFieldToFilter('order_id',  $data['order_id'])
                    ->setPageSize(1)
                    ->setCurPage(1);
        if(array_key_exists('status_shipment', $data))
        {
            $collection->addFieldToFilter('status_shipment', $data['status_shipment']);
        }
        if(array_key_exists('status_payment', $data))
        {
            $collection->addFieldToFilter('status_payment', $data['status_payment']);
        }


        if($collection->getSize())
        {
            try{
                $collection->getFirstItem()->setData($data)->save();
            }catch(\Exception $e){
                throw $e;
            }

        }
        else
        {
            try{
                $this->_orderPayshipModel->setData($data)->save();
            }catch(\Exception $e){
                throw $e;
            }

        }
    }

    /**
     * @param $paymentMethod
     * @param $orderId
     * @return mixed
     */
    public function getPaymentDate($paymentMethod,$orderId)
    {
        switch($paymentMethod)
        {
            case 'paygent':
                $shipstep = 'shipped_out';
                break;
            case 'cvspayment':
                $shipstep = 'imported';
                break;
            default:
                $shipstep = 'delivery_complete';
                break;
        }
        $collection = $this->_orderPayshipCollection
            ->addFieldToFilter('order_id',$orderId)
            ->addFieldToFilter('status_shipment',$shipstep)->load();
        if($collection->getSize())
        {
            return $collection->getFirstItem()->getStatusDate();
        }
    }

    /**
     * @param $shipmentStatus
     * @param $orderId
     * @return mixed
     */
    public function getShipmentDate($shipmentStatus,$orderId)
    {
        $collection = $this->_orderPayshipCollection
            ->addFieldToFilter('order_id',$orderId)
            ->addFieldToFilter('status_shipment',$shipmentStatus)->load();
        if($collection->getSize())
        {
            return $collection->getFirstItem()->getStatusDate();
        }
    }

    /*
     * update order payment status for list order
     * @param $order, array ( list order id )
    */
    public function updateOrderPaymentStatus($order)
    {
        $this->_orderResource->getConnection()->update(
            $this->_orderResource->getMainTable(),
            ['payment_status' => \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED,],
            ['entity_id IN(?)' => $order]
        );

        $this->_orderResource->getConnection()->update(
            'sales_order_grid',
            ['payment_status' => \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED,],
            ['entity_id IN(?)' => $order]
        );
    }
}
