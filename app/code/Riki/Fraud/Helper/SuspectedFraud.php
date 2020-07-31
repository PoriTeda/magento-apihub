<?php

namespace Riki\Fraud\Helper;

class SuspectedFraud extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;
    /**
     * @var \Riki\Fraud\Model\SuspectedFraudFactory
     */
    protected $_suspectedFactory;
    /**
     * @var \Riki\Fraud\Model\ResourceModel\SuspectedFraud\CollectionFactory
     */
    protected $_suspectedCollection;

    /**
     * SuspectedFraud constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\Fraud\Model\SuspectedFraudFactory $suspectedFactory
     * @param \Riki\Fraud\Model\ResourceModel\SuspectedFraud\CollectionFactory $collection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Fraud\Model\SuspectedFraudFactory $suspectedFactory,
        \Riki\Fraud\Model\ResourceModel\SuspectedFraud\CollectionFactory $collection
    ){
        parent::__construct($context);
        $this->_dateTime = $dateTime;
        $this->_authSession = $authSession;
        $this->_suspectedFactory = $suspectedFactory;
        $this->_suspectedCollection = $collection;
    }

    /**
     * @param $order
     * @return bool
     */
    public function suspectedOrder($order){
        $model = $this->getSuspectedOrder( $order->getId() );
        $model->setOrderId( $order->getId() );
        $model->setOrderIncrementId( $order->getIncrementId() );
        $model->setCustomerId( $order->getCustomerId() );
        $model->setCustomerEmail( $order->getCustomerEmail() );
        $model->setChangeStatusSuspicious(1);
        $model->setSendEmail(1);
        try {
            $model->save();
        } catch (\Exception $e){
            $this->_logger->critical($e->getMessage());
        }
        return true;
    }

    /**
     * @param $orderId
     * @return bool
     */
    public function approvedOrder($orderId){
        $model = $this->getSuspectedOrderById($orderId);
        if( !empty($model) ){
            $model->setApprovalDate( $this->_dateTime->date('Y-m-d H:i:s') );
            $model->setUserId( $this->_authSession->getUser()->getId() );
            $model->setUserName( $this->_authSession->getUser()->getUserName() );
            try {
                $model->save();
            } catch (\Exception $e){
                $this->_logger->critical($e->getMessage());
            }
        }
        return true;
    }

    /**
     * @param $orderId
     * @return \Magento\Framework\DataObject|\Riki\Fraud\Model\SuspectedFraud
     */
    public function getSuspectedOrder( $orderId ){
        $model = $this->getSuspectedOrderById($orderId);
        if( !empty($model) ){
            return $model;
        } else {
            return $this->_suspectedFactory->create();
        }
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getSuspectedOrderById( $orderId ){
        $suspectedCollection = $this->_suspectedCollection->create();
        $suspectedCollection->addFieldToFilter('order_id', $orderId);
        if( $suspectedCollection->getSize() ){
            return $suspectedCollection->getFirstItem();
        } else {
            return false;
        }
    }
}
