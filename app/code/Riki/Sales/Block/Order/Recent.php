<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Order;

/**
 * Sales order history block
 */
class Recent extends \Magento\Sales\Block\Order\Recent
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
     * Recent constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Sales\Helper\Data $helper
     * @param \Riki\Preorder\Model\ResourceModel\OrderPreorder $orderPreorderModel
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Sales\Helper\Data $helper,
        \Riki\Preorder\Model\ResourceModel\OrderPreorder $orderPreorderModel,
        array $data = []
    ) {
        $this->_orderStatusCollectionFactory = $orderStatusCollectionFactory;

        $this->_datetime = $dateTime;

        $this->_timezone = $context->getLocaleDate();

        $this->_helper = $helper;
        $this->_orderPreorderModel = $orderPreorderModel;

        parent::__construct($context, $orderCollectionFactory, $customerSession, $orderConfig, $data);
    }

    /**
     * Get Orders
     *
     * @return $this
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

        $orders = $this->_orderCollectionFactory->create()->addAttributeToSelect(
            '*'
        )->addAttributeToFilter(
            'customer_id',
            $this->_customerSession->getCustomerId()
        )->addAttributeToFilter(
            'status',
            ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
        )->addAttributeToSort(
            'created_at',
            'desc'
        )->addFieldToFilter(
            'created_at',
            ['lteq' => $nowTimezone]
        )->addFieldToFilter(
            'created_at',
            ['gteq' => $nowBeforeTimezone]
        )->setPageSize(
            '5'
        )->load();

        return $orders;
    }

    /**
     * Get Color Status
     *
     * @param $codeStatus
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
}
