<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Order;

use Magento\Framework\Exception\LocalizedException;
use Riki\ArReconciliation\Model\OrderLog;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Riki\ArReconciliation\Helper\Data
     */
    protected $_dataHelper;
    /*
     * @var Riki\ArReconciliation\Model\ResourceModel\OrderLog\CollectionFactory
     */
    protected  $_orderLog;
    /*
     * admin who try to change shipment data
     */
    protected $_userId;
    protected $_userName;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param OrderLog $orderLog
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\ArReconciliation\Helper\Data $helper,
        OrderLog $orderLog
    ) {
        $this->_coreRegistry = $registry;
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_logger = $logger;
        $this->_dataHelper = $helper;
        $this->_orderLog = $orderLog;
        $this->_userId = $authSession->getUser()->getId();
        $this->_userName = $authSession->getUser()->getUserName();

        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Sales::sales_order_reconciliation');
    }

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('order_id');
            $order = $this->_getOrderById($id);
            $amount = trim($this->getRequest()->getParam('nestle_payment_amount'));
            $date = trim( $this->getRequest()->getParam('nestle_payment_date') );

            if ($amount == "") {
                throw new LocalizedException(__('Please enter a collected amount.'));
            }

            if (empty($date)) {
                throw new LocalizedException(__('Please enter a collected date.'));
            }

            $paymentReconciliation = 0;

            if (!empty( $this->getRequest()->getParam('nestle_payment_reconciliation'))) {
                $paymentReconciliation = $this->getRequest()->getParam('nestle_payment_reconciliation');
            }
            /*flag to check that we need to generate history change log*/
            $generateLog = false;
            $changeAmount = false;
            $changeDate = false;

            if ($order->getData('nestle_payment_amount') != $amount) {
                $generateLog = true;
                $changeAmount = true;
            }

            if ($order->getData('nestle_payment_date') != $date) {
                $generateLog = true;
                $changeDate = true;
            }

            if ($generateLog == true) {
                $oldData = array(
                    'nestle_payment_amount' => $order->getData('nestle_payment_amount'),
                    'nestle_payment_date' => $order->getData('nestle_payment_date')
                );
                $order->setData( 'nestle_payment_amount', $amount );
                $order->setData( 'nestle_payment_date', $date );
            }

            $order->setData( 'nestle_payment_reconciliation', $paymentReconciliation );

            $order->save();

            if ($generateLog == true) {
                $this->generateLog(
                    $order, $oldData,
                    $this->_dataHelper->getChangeLogMessage(
                        $changeAmount, $changeDate,  \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT
                    ),
                    $this->_dataHelper->getChangeType($changeAmount, $changeDate)
                );
            }
            $this->_coreRegistry->register('current_order', $order);
            $res = $this->_view->getLayout()->createBlock('Riki\ArReconciliation\Block\Adminhtml\Orders\Collected')->toHtml();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $res = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $res = ['error' => true, 'message' => __( $e->getMessage() )];
        }

        if (is_array($res)) {
            $res = $this->_jsonHelper->jsonEncode( $res );
            $this->getResponse()->representJson( $res );
        } else {
            $this->getResponse()->setBody( $res );
        }
    }

    /**
     * @param $or
     * @param $oldData
     * @param $msg
     * @param $changeType
     * @return bool
     */
    private function generateLog( $or, $oldData, $msg, $changeType )
    {
        $model = $this->_orderLog;

        $model->setData( array(
            'user_id' => $this->_userId,
            'user_name' => $this->_userName,
            'order_id' => $or->getId(),
            'order_increment_id' => $or->getIncrementId(),
            'nestle_payment_amount' => $or->getData('nestle_payment_amount'),
            'nestle_payment_date' => $or->getData('nestle_payment_date'),
            'change_type' => $changeType,
            'log' => \Zend_Json::encode( $oldData ),
            'type' => OrderLog::TYPE_MANUALLY,
            'note' => $msg,
            'created' => $this->_dateTime->date(),
        ));

        try{
            $model->save();
        } catch (\Exception $e) {
            $this->_logger->error( $e->getMessage() );
        }

        return true;
    }

    /**
     * @param $orderId
     * @return bool
     */
    protected function _getOrderById($orderId)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->_orderRepository->get($orderId);

        if ($order->getId()) {
            return $order;
        }

        return false;
    }
}
