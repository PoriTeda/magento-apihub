<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Shipment;

use Magento\Framework\Exception\LocalizedException;
use Riki\ArReconciliation\Model\ShipmentLog;

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
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
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
     * @var Riki\ArReconciliation\Model\ResourceModel\ShipmentLog\CollectionFactory
     */
    protected  $_shipmentLog;
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
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param ShipmentLog $shipmentLog
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\ArReconciliation\Helper\Data $helper,
        ShipmentLog $shipmentLog
    ) {
        $this->_coreRegistry = $registry;
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_logger = $logger;
        $this->_dataHelper = $helper;
        $this->_shipmentLog = $shipmentLog;
        $this->_userId = $authSession->getUser()->getId();
        $this->_userName = $authSession->getUser()->getUserName();
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('shipment_id');
            $shipment = $this->_getShipmentById($id);
            $amount = trim($this->getRequest()->getParam('nestle_payment_amount'));

            if( $amount == "" ){
                throw new LocalizedException(__('Please enter a collected amount.'));
            }

            $date = trim( $this->getRequest()->getParam('nestle_payment_date') );

            if( empty($date) ){
                throw new LocalizedException(__('Please enter a collected date.'));
            }

            $paymentReconciliation = 0;

            if( !empty( $this->getRequest()->getParam('nestle_payment_reconciliation') ) )
            {
                $paymentReconciliation = $this->getRequest()->getParam('nestle_payment_reconciliation');
            }

            /*flag to check that we need to generate history change log*/
            $generateLog = false;
            $changeAmount = false;
            $changeDate = false;

            if( $shipment->getData('nestle_payment_amount') != $amount )
            {
                $generateLog = true;
                $changeAmount = true;
            }

            if( $shipment->getData('nestle_payment_date') != $date )
            {
                $generateLog = true;
                $changeDate = true;
            }

            if($generateLog == true)
            {
                $oldData = array(
                    'nestle_payment_amount' => $shipment->getData('nestle_payment_amount'),
                    'nestle_payment_date' => $shipment->getData('nestle_payment_date')
                );
                $shipment->setData( 'nestle_payment_amount', $amount );
                $shipment->setData( 'nestle_payment_date', $date );
            }

            $shipment->setData('nestle_payment_reconciliation', $paymentReconciliation );

            $shipment->save();

            if( $generateLog == true )
            {
                $this->generateLog(
                    $shipment, $oldData,
                    $this->_dataHelper->getChangeLogMessage(
                        $changeAmount, $changeDate, \Riki\ArReconciliation\Helper\Data::PAYMENT_OBJECT
                    ),
                    $this->_dataHelper->getChangeType($changeAmount, $changeDate)
                );
            }

            $this->_coreRegistry->register('current_shipment', $shipment);

            $res = $this->_view->getLayout()->createBlock('Riki\ArReconciliation\Block\Adminhtml\Shipment')->toHtml();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $res = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $res = ['error' => true, 'message' => __( $e->getMessage() )];
        }

        if ( is_array($res) )
        {
            $res = $this->_jsonHelper->jsonEncode( $res );
            $this->getResponse()->representJson( $res );
        }
        else
        {
            $this->getResponse()->setBody( $res );
        }
    }

    /**
     * @param $sm
     * @param $oldData
     * @param $msg
     * @param $changeType
     * @return bool
     */
    private function generateLog( $sm, $oldData, $msg, $changeType )
    {
        $model = $this->_shipmentLog;

        $model->setData( array(
            'user_id' => $this->_userId,
            'user_name' => $this->_userName,
            'shipment_id' => $sm->getId(),
            'shipment_increment_id' => $sm->getIncrementId(),
            'nestle_payment_amount' => $sm->getData('nestle_payment_amount'),
            'nestle_payment_date' => $sm->getData('nestle_payment_date'),
            'change_type' => $changeType,
            'log' => \Zend_Json::encode( $oldData ),
            'type' => ShipmentLog::TYPE_MANUALLY,
            'note' => $msg,
            'created' => $this->_dateTime->date()
        ));

        try{
            $model->save();
        } catch ( \Magento\Framework\Validator\Exception $e ){
            $this->_logger->error( $e->getMessage() );
        }

        return true;
    }

    /**
     * get shipment by id
     *
     * @param $shipmentId
     * @return bool
     */
    protected function _getShipmentById($shipmentId)
    {
        /** @var \Magento\Sales\Api\Data\ShipmentInterface $shipment */
        $shipment = $this->_shipmentRepository->get($shipmentId);

        if ($shipment->getId()) {
            return $shipment;
        }

        return false;
    }
}
