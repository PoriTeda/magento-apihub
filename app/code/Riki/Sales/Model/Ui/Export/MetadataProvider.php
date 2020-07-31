<?php
namespace Riki\Sales\Model\Ui\Export;

class MetadataProvider extends \Riki\Catalog\Model\Export\MetadataProvider
{
    protected $currentOrderNumber;
    protected $currentPaymentStatus;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    public function __construct(
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        $dateFormat = 'M j, Y H:i:s A',
        array $data = []
    ) {
        parent::__construct($filter, $localeDate, $localeResolver, $dateFormat, $data);
        $this->_orderRepository = $orderRepository;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
    }

    /**
     * Returns row data
     *
     * @param \Magento\Framework\Api\Search\DocumentInterface $document
     * @param array $fields
     * @param array $options
     * @return array
     */
    public function getRowData(\Magento\Framework\Api\Search\DocumentInterface $document, $fields, $options)
    {
        $row = [];
        foreach ($fields as $column) {

            if($column == 'customer_membership'){
                if (isset($options[$column])) {
                    $key = $document->getCustomAttribute($column)->getValue();

                    $values = [];
                    if(!empty($key)){
                        $keys = explode(',', $key);

                        foreach($keys as $key){
                            if (isset($options[$column][$key])) {
                                $values[] = $options[$column][$key];
                            }
                        }
                    }

                    $row[] = implode(',', $values);
                } else {
                    $row[] = $document->getCustomAttribute($column)->getValue();
                }
            }else{
                if (isset($options[$column])) {
                    $key = $document->getCustomAttribute($column)->getValue();
                    if (isset($options[$column][$key])) {
                        $row[] = $options[$column][$key];
                    } else {
                        $row[] = '';
                    }
                } else {

                    if( $column =='increment_id' ){
                        $this->setOrderNumber($document->getCustomAttribute($column)->getValue());
                    } elseif ( $column == 'payment_status' ){
                        $this->setPaymentStatus($document->getCustomAttribute($column)->getValue());
                    }

                    if( $column == 'collected_date' ){
                        $row[] = $this->orderCollectedDate();
                    } else {
                        $row[] = $document->getCustomAttribute($column)->getValue();
                    }
                }
            }
        }

        return $row;
    }

    /*set current order number for current row*/
    public function setOrderNumber($number)
    {
        $this->currentOrderNumber = $number;
    }

    /*get current order number for current row*/
    public function getOrderNumber()
    {
        return $this->currentOrderNumber;
    }

    /*set current order number for current row*/
    public function setPaymentStatus($status)
    {
        $this->currentPaymentStatus = $status;
    }

    /*get current order number for current row*/
    public function getPaymentStatus()
    {
        return $this->currentPaymentStatus;
    }

    /*get order collected date*/
    public function orderCollectedDate()
    {
        if( $this->getPaymentStatus() == \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED )
        {
            $order = $this->_getOrderByIncrementId($this->getOrderNumber());
            if ($order) {
                if( $order->getPaymentStatus() == \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED )
                {
                    $collectedDate = $this->_getOrderCollectedDate($order->getId());
                    if( !empty( $collectedDate ) )
                    {
                        return $collectedDate;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get order by increment id
     *
     * @param $orderId
     * @return bool
     */
    public function _getOrderByInCrementId($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $orderId )
            ->create();

        /** @var \Magento\Sales\Api\Data\OrderSearchResultInterface $orderCollection */
        $orderCollection = $this->_orderRepository->getList($criteria);

        if ($orderCollection->getTotalCount()) {
            return $orderCollection->getFirstItem();
        }

        return false;
    }

    /**
     * Get order collected date
     *
     * @param $orderId
     * @return bool
     */
    public function _getOrderCollectedDate($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder
            ->addFilter('order_id', $orderId)
            ->addFilter('payment_status', \Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED)
            ->create();

        /** @var \Magento\Sales\Api\Data\ShipmentSearchResultInterface $shipCollection */
        $shipCollection = $this->_shipmentRepository
            ->getList($criteria)
            ->setOrder('payment_date');

        if ($shipCollection->getTotalCount()) {
            return $shipCollection->getFirstItem()->getPaymentDate();
        }

        return false;
    }
}