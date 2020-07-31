<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ReceiveCvsPayment\Cron;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class UpdateOrder
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce

 */
class UpdateOrder
{
    const PREPARING_FOR_SHIPPING = 'preparing_for_shipping';
    /**
     * @var
     */
    protected $_dateTime;
    /**
     * @var
     */
    protected $_logger;
    /**
     * @var
     */
    protected $_dataHelper;
    /**
     * UpdateOrder constructor.
     */
    protected $_objectManager;
    /**
     * @var \Riki\AutomaticallyShipment\Model\CreateShipment
     */
    protected $_createShipment;
    /**
     * @var \Riki\Sales\Helper\OrderStatus
     */
    protected $_orderStatusHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var Magento\Sales\Model\ResourceModel\Order
     */
    protected $_orderResource;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory
     */
    protected $_importingCollectionFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory|\Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * UpdateOrder constructor.
     * @param \Riki\ReceiveCvsPayment\Helper\Data $dataHelper
     * @param \Riki\ReceiveCvsPayment\Logger\LoggerCvs $logger
     * @param \Magento\Sales\Model\ResourceModel\Order $orderResource
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $dateTime
     * @param \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment
     * @param \Riki\Sales\Helper\OrderStatus $orderStatusHelper
     * @param \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory $collectionFactory
     * @param \Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory $orderCollectionFactory
     */
    public function __construct(

        \Riki\ReceiveCvsPayment\Helper\Data $dataHelper,
        \Riki\ReceiveCvsPayment\Logger\LoggerCvs $logger,
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment,
        \Riki\Sales\Helper\OrderStatus $orderStatusHelper,
        \Riki\ReceiveCvsPayment\Model\ResourceModel\Importing\CollectionFactory $collectionFactory,
        \Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory $orderCollectionFactory
    ) {
        $this->_logger = $logger;
        $this->_dataHelper = $dataHelper;
        $this->_orderRepository = $orderRepository;
        $this->_dateTime = $dateTime;
        $this->_createShipment = $createShipment;
        $this->_orderStatusHelper = $orderStatusHelper;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderResource = $orderResource;
        $this->_importingCollectionFactory = $collectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Excecute the cron
     */
    public function execute()
    {
        if (!$this->_dataHelper->isEnable()) {
            $this->_logger->info('Function has been disabled.');
            return;
        }
        $orderStatus = $this->_dataHelper->getStatusList();
        //get collection of upload file
        $importingCollection = $this->_importingCollectionFactory->create();
        $unImport = \Riki\ReceiveCvsPayment\Model\Config\Source\StatusOption::STATUS_UNIMPORT;
        $imported = \Riki\ReceiveCvsPayment\Model\Config\Source\StatusOption::STATUS_IMPORTED;
        $importingCollection->addFieldtoSelect('*');
        $importingCollection->addFieldToFilter('status', ['eq' => $unImport]);
        $importingCollection->load();
        if ($importingCollection->getSize() > 0) {
            foreach ($importingCollection as $importer) {
                //get order increment collection from imported CSV
                $orderCollection = $this->_orderCollectionFactory->create();
                $orderCollection->addFieldtoSelect('*')
                    ->addFieldToFilter('csv_id', ['eq' => $importer->getId()])
                    ->addFieldToFilter('status', ['eq' => 0])
                    ->load();
                if ($orderCollection->getSize())
                {
                    $ordersuccess = array();
                    $orderfail = array();
                    $messsagesInternal = '';
                    foreach ($orderCollection as $item) {

                        $orderIncrementId = $item->getOrderIncrement();

                        $order = $this->_getOrderByIncrementId($orderIncrementId);

                        if ($order && in_array($order->getStatus(),$orderStatus)) {
                            //update order
                            if ($order->canShip()) {
                                //create shipment
                                try {
                                    $this->_createShipment->createShipment($order, __('Update CVS Payment Order Cron'));
                                    $order->addStatusToHistory(self::PREPARING_FOR_SHIPPING, __('Update order success , Import from Welnet'));
                                    $order->save();
                                    $ordersuccess[] = $orderIncrementId;
                                    //store status
                                    $statusData = array(
                                        'order_id' => $order->getId(),
                                        'order_increment_id' => $order->getIncrementId(),
                                        'status_payment' => \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED
                                    );
                                    $this->_orderStatusHelper->addOrderPayShipStatus($statusData);

                                } catch (\Exception $e) {
                                    throw $e;
                                }
                                //update CVS order
                            } else {
                                $orderfail[] = $orderIncrementId;
                            }
                        } else // invalid or not found
                        {
                            $orderfail[] = $orderIncrementId;
                        }//end else
                        //update status
                        try {
                            $item->setStatus(1)->save();
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }//end foreach
                    //log the results
                    if ($ordersuccess) {
                        $this->updateOrderPaymentStatus( $ordersuccess);
                        $messsagesInternal .= sprintf(__(" Update orders success: %s"), implode(",", $ordersuccess));
                    }
                    if ($orderfail) {
                        $messsagesInternal .= sprintf(__(" Update orders fail: %s"), implode(",", $orderfail));
                    }
                } else {
                    $messsagesInternal = __('Orders not found');
                    //end if
                }
                //finish importing
                $importer->setMessages($messsagesInternal);
                $importer->setStatus($imported);
                $importer->setImported($this->_dateTime->date()->format('Y-m-d H:i:s'));
                try {
                    $importer->save();
                } catch (\Exception $e) {
                    throw $e;
                }

            }
        }//end if
    }//end function

    /**
     * @param $orderIncrementId
     * @return bool|array
     */
    protected function _getOrderByIncrementId($orderIncrementId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $orderIncrementId)
            ->create();

        $orderCollection = $this->_orderRepository->getList($criteria);
        if ($orderCollection->getSize()) {
            return $orderCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $order
     */
    protected function updateOrderPaymentStatus( $order )
    {
        $this->_orderResource->getConnection()->update(
            $this->_orderResource->getMainTable(),
            ['payment_status' => \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED],
            ['increment_id IN(?)' => $order]
        );

        $this->_orderResource->getConnection()->update(
            'sales_order_grid',
            ['payment_status' => \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED],
            ['increment_id IN(?)' => $order]
        );
    }

}//end class