<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Order;

/**
 * Sales order history block
 */
class History extends \Magento\Sales\Block\Order\History
{
    protected $_orderStatusCollectionFactory ;

    protected $_timezone;

    protected $_datetime;

    protected $_helper;
    /**
     * @var \Riki\Preorder\Model\ResourceModel\OrderPreorder
     */
    protected  $_orderPreorderModel;
    /**
     * @var \Riki\ThirdPartyImportExport\Model\Order\DetailFactory
     */
    protected $_legacyFactory;
    /**
     * @var
     */
    protected $_ordersLegacy;
    /**
     * @var
     */
    protected $_orderIds;
    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Data
     */
    protected $_dataHelperLegacy;
    /**
     * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionLegacyFactory;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;
    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * History constructor.
     * @param \Magento\Framework\View\Element\Template\Context                         $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory               $orderCollectionFactory
     * @param \Magento\Customer\Model\Session                                          $customerSession
     * @param \Magento\Sales\Model\Order\Config                                        $orderConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory        $orderStatusCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime                              $dateTime
     * @param \Riki\Preorder\Model\ResourceModel\OrderPreorder                         $orderPreorderModel
     * @param \Riki\ThirdPartyImportExport\Model\Order\DetailFactory                   $legacyFactory
     * @param \Riki\ThirdPartyImportExport\Helper\Data                                 $dataHelperLegacy
     * @param \Riki\Sales\Helper\Data                                                  $helper
     * @param \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory $collectionLegacyFactory
     * @param \Riki\Framework\Helper\Search                                            $searchHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface                $outOfStockRepository
     * @param array                                                                    $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Preorder\Model\ResourceModel\OrderPreorder $orderPreorderModel,
        \Riki\ThirdPartyImportExport\Model\Order\DetailFactory $legacyFactory,
        \Riki\ThirdPartyImportExport\Helper\Data  $dataHelperLegacy,
        \Riki\Sales\Helper\Data $helper,
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\CollectionFactory $collectionLegacyFactory,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        array $data = []
    ) {
        $this->_orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->_datetime = $dateTime;
        $this->_timezone = $context->getLocaleDate();
        $this->_helper = $helper;
        $this->_orderPreorderModel = $orderPreorderModel;
        $this->_legacyFactory = $legacyFactory;
        $this->_orderCollectionLegacyFactory = $collectionLegacyFactory;
        $this->_dataHelperLegacy = $dataHelperLegacy;
        $this->searchHelper = $searchHelper;
        $this->outOfStockRepository = $outOfStockRepository;
        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }


    /**
     * Get Orders
     *
     * @return $this|bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrders()
    {
        $visibilityMonth = (int)$this->_helper->getVisibilityMonths();
        $visibilityMonth--;
        $visibilityMonth = ($visibilityMonth < 0)?0:$visibilityMonth;


        $now = $this->_datetime->gmtDate();
         $nowTimezone = $this->_timezone->date($now)->format('Y-m-d H:i:s');

        $nowBefore = strtotime($now." -".$visibilityMonth." months");
        $nowBeforeTimezone = $this->_timezone->date($nowBefore)->format('Y-m-01 00:00:00');

        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }

        if (!$this->orders) {
            $this->orders = $this->_orderCollectionFactory->create()->addFieldToSelect(
                '*'
            )->addFieldToFilter(
                'customer_id',
                $customerId
            )->addFieldToFilter(
                'status',
                ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
            )->addFieldToFilter(
                'created_at',
                ['lteq' =>  $nowTimezone]
            )->addFieldToFilter(
                'created_at',
                ['gteq' => $nowBeforeTimezone]
            )->setOrder(
                'created_at',
                'desc'
            );
        }
        return $this->orders;
    }

    /**
     * Get Order Ids
     *
     * @return $this|bool|\Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderIds()
    {
        if (!($customerId = $this->_customerSession->getCustomerId())) {
            return false;
        }

        if (!$this->_orderIds) {
            $this->_orderIds = $this->_orderCollectionFactory->create()->addFieldToSelect(
                'entity_id'
            )->addFieldToFilter(
                'customer_id',
                $customerId
            )->addFieldToFilter(
                'status',
                ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
            );
        }

        $invisibleOrderIds = $this->searchHelper
            ->getByCallbackMethod($customerId, 'getInvisibleOrderIdsByCustomerId')
            ->execute($this->outOfStockRepository);
        if ($invisibleOrderIds) {
            $this->_orderIds->addFieldToFilter('entity_id', ['nin' => $invisibleOrderIds]);
        }
        $this->_orderIds->getSelect()->limit(1);
        return $this->_orderIds;
    }

    /**
     * get list product name on order item
     *
     * @param $orderItems
     * @return array|null|string
     */
    public function getListProductName($orderItems){
        $productString = null;
        $listProduct= $orderItems->getAllItems();
        if(is_array($listProduct) && count($listProduct)>0){
            $tmp = [];
            foreach ($listProduct as $product){
                $tmp[$product->getId()] = $product->getName();
            }
            $productString =  implode(', ',$tmp);
        }
        return $productString;
    }

    /**
     * get list product name legacy
     *
     * @param $orderItems
     * @return array|null|string
     */
    public function getListProductNameLegacy($orderItems){
        $productString = null;
        $items = $this->_legacyFactory->create()->getCollection()->addFieldToFilter('order_no',$orderItems->getOrderNo());
        if($items && $items->getSize()>0){
            $tmp = [];
            foreach ($items as $_item){
                $tmp[$_item->getCommodityName()] = $_item->getCommodityName();
            }
            $productString =  implode(', ',$tmp);
        }
        return $productString;
    }

    /**
     * Get Color Status
     *
     * @param $codeStatus
     *
     * @return string
     */
    public function getColorStatus($codeStatus)
    {

        $statusColor = $this->_orderStatusCollectionFactory->create()
            ->addFieldToSelect('color_code')
            ->addFieldToFilter('status', $codeStatus)
        ->setPageSize(1)->setCurPage(1);
        $coloCode = $statusColor->getData('color_code');
        if(isset($coloCode[0]['color_code'])){
            return $coloCode[0]['color_code'] ;
        }
        return '' ;
    }

    /**
     * @param $idOrder
     * @return bool
     */
    public function checkPreOrder($idOrder){
        return $this->_orderPreorderModel->getOrderIsPreorderFlag($idOrder);
    }

    /**
     * Get order type
     *
     * @param $order
     * @return \Magento\Framework\Phrase
     *      order is pre order -> return Pre-order
     *      else -> return riki Type { spot, subscription, hanpukai }
     */
    public function getOrderType($order)
    {
        if ($this->checkPreOrder($order->getId())) {
            return __(\Riki\Preorder\Model\Order\OrderType::PREORDER_LABEL);
        }
        return __($order->getRikiType());
    }
    /**
     * Get orders match consumer id
     *
     * @return array|\Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Collection
     */
    public function getOrdersLegacy()
    {

        if ($this->_ordersLegacy) {
            return $this->_ordersLegacy;
        }
        $customer = $this->_customerSession->getCustomer();
        if (!$customer->getId()) {
            return [];
        }

        if (!($consumerId = $customer->getData('consumer_db_id'))) {
            return [];
        }

        $xYear = (int)$this->_dataHelperLegacy->getConfig(\Riki\ThirdPartyImportExport\Helper\Data::CONFIG_ORDER_IMPORT_X_YEAR);
        $currentDate = $this->_timezone->date();
        $currentDate = $currentDate->sub(date_interval_create_from_date_string($xYear.' years'));

        $this->_ordersLegacy = $this->_orderCollectionLegacyFactory->create();
        $this->_ordersLegacy->addFieldToSelect('order_no')
            ->addFieldToFilter('customer_code', $consumerId)
            ->addFieldToFilter('free_shipping_flag', ['neq' => 1])
            ->addFieldToFilter('payment_method_type', ['neq' => '00'])
            ->addFieldToFilter('order_datetime', ['gteq' => $currentDate->format('Y-m-d H:i:s')])
            ->setOrder('created_datetime', 'desc')
            ->getSelect()
            ->limit(1);

        return $this->_ordersLegacy;
    }

    /**
     * @param null $date
     * @param int $format
     * @param bool $showTime
     * @param null $timezone
     * @return string
     */
    public function formatDate(
        $date = null,
        $format = \IntlDateFormatter::SHORT,
        $showTime = false,
        $timezone = null
    )
    {
        if (date('Y-m-d H:i:s', strtotime($date)) != $date) {
            return '';
        }

        return parent::formatDate($date, $format, $showTime, $timezone); // TODO: Change the autogenerated stub
    }
}
