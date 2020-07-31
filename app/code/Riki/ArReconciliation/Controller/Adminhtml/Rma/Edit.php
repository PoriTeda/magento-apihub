<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Rma;

use Magento\Framework\Exception\LocalizedException;
use Riki\ArReconciliation\Model\ReturnLog;

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
     * @var \Magento\Rma\Api\RmaRepositoryInterface
     */
    protected $_rmaRepository;
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
     * @var Riki\ArReconciliation\Model\ResourceModel\returnLog\CollectionFactory
     */
    protected  $_returnLog;

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
     * @param \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\ArReconciliation\Helper\Data $helper
     * @param ReturnLog $returnLog
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\ArReconciliation\Helper\Data $helper,
        ReturnLog $returnLog
    ) {
        $this->_coreRegistry = $registry;
        $this->_dateTime = $dateTime;
        $this->_jsonHelper = $jsonHelper;
        $this->_rmaRepository = $rmaRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_logger = $logger;
        $this->_dataHelper = $helper;
        $this->_returnLog = $returnLog;
        $this->_userId = $authSession->getUser()->getId();
        $this->_userName = $authSession->getUser()->getUserName();
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return_reconciliation');
    }

    public function execute()
    {
        try {

            $id = $this->getRequest()->getParam('rma_id');

            $rma = $this->_getRmaById($id);

            $amount = trim($this->getRequest()->getParam('nestle_refund_amount'));

            $date = trim( $this->getRequest()->getParam('nestle_refund_date') );

            if( $amount == "" ){
                throw new LocalizedException(__('Please enter a refund amount.'));
            }

            if( empty($date) ){
                throw new LocalizedException(__('Please enter a refund date.'));
            }

            $paymentReconciliation = 0;

            if (!empty( $this->getRequest()->getParam('nestle_payment_reconciliation') )) {
                $paymentReconciliation = $this->getRequest()->getParam('nestle_payment_reconciliation');
            }

            /*flag to check that we need to generate history change log*/
            $generateLog = false;
            $changeAmount = false;
            $changeDate = false;

            if ($rma->getData('nestle_refund_amount') != $amount) {
                $generateLog = true;
                $changeAmount = true;
            }

            if ($rma->getData('nestle_refund_date') != $date) {
                $generateLog = true;
                $changeDate = true;
            }

            if ($generateLog == true) {
                $oldData = array(
                    'nestle_refund_amount' => $rma->getData('nestle_refund_amount'),
                    'nestle_refund_date' => $rma->getData('nestle_refund_date')
                );
                $rma->setData( 'nestle_refund_amount', $amount );
                $rma->setData( 'nestle_refund_date', $date );
            }

            $rma->setData('nestle_payment_reconciliation', $paymentReconciliation );

            $rma->save();

            if ($generateLog == true) {
                $this->generateLog(
                    $rma, $oldData,
                    $this->_dataHelper->getChangeLogMessage(
                        $changeAmount, $changeDate, \Riki\ArReconciliation\Helper\Data::RETURN_OBJECT
                    ),
                    $this->_dataHelper->getChangeType($changeAmount, $changeDate)
                );
            }
            $this->_coreRegistry->register('current_rma', $rma);
            $res = $this->_view->getLayout()->createBlock('Riki\ArReconciliation\Block\Adminhtml\Rma\Rma')->toHtml();

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $res = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $res = ['error' => true, 'message' => __( $e->getMessage() )];
        }

        if (is_array($res)) {
            $res = $this->_jsonHelper->jsonEncode($res);

            $this->getResponse()->representJson($res);
        } else {
            $this->getResponse()->setBody($res);
        }
    }

    /**
     * @param $rt
     * @param $oldData
     * @param $msg
     * @param $changeType
     * @return bool
     */
    private function generateLog( $rt, $oldData, $msg, $changeType )
    {
        $model = $this->_returnLog;

        $model->setData( array(
            'user_id' => $this->_userId,
            'user_name' => $this->_userName,
            'rma_id' => $rt->getId(),
            'rma_increment_id' => $rt->getIncrementId(),
            'nestle_refund_amount' => $rt->getData('nestle_refund_amount'),
            'nestle_refund_date' => $rt->getData('nestle_refund_date'),
            'change_type' => $changeType,
            'log' => \Zend_Json::encode( $oldData ),
            'type' => ReturnLog::TYPE_MANUALLY,
            'note' => $msg,
            'created' => $this->_dateTime->date()
        ));

        try{
            $model->save();
        } catch (\Exception $e ){
            $this->_logger->error( $e->getMessage() );
        }

        return true;
    }

    /**
     * @param $rmaId
     * @return bool
     */
    protected function _getRmaById($rmaId)
    {
        /** @var \Magento\Rma\Api\Data\RmaInterface $rma */
        $rma = $this->_rmaRepository->get($rmaId);

        if ($rma->getId()) {
            return $rma;
        }

        return false;
    }
}
