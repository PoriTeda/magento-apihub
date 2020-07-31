<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Payment;

use Riki\ArReconciliation\Model\OrderPaymentStatusLog;

class Edit extends \Magento\Backend\App\Action
{
    protected $_jsonHelper;

    protected $resultPageFactory;

    protected $_coreRegistry = null;

    protected $_dateTime;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Riki\ArReconciliation\Model\OrderPaymentStatusLog
     */
    protected $_orderPaymentStatusLog;

    /*
     * admin who try to change payment status
     */
    protected $_userId;

    protected $_userName;


    /**
     * @param \Magento\Backend\App\Action\Context $context
     * \Magento\Sales\Model\ResourceModel\Orders\ShipmentLog\CollectionFactory $shipmentCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        OrderPaymentStatusLog $orderPaymentStatusLog
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_jsonHelper = $jsonHelper;
        $this->_coreRegistry = $registry;
        $this->_userId = $authSession->getUser()->getId();
        $this->_userName = $authSession->getUser()->getUserName();
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderPaymentStatusLog = $orderPaymentStatusLog;
        $this->_dateTime = $dateTime;
        parent::__construct($context);
    }

    public function execute()
    {
        try {

            $id = $this->getRequest()->getParam('order_id');

            $order = $this->_getOrderById($id);

            $paymentStatus = trim($this->getRequest()->getParam('payment_status'));

            /*flag to check that we need to generate history change log*/
            $generateLog = false;

            if( $order->getPaymentStatus() != $paymentStatus )
            {
                $generateLog = true;
                $previousStatus = $order->getPaymentStatus();
                $order->setPaymentStatus( $paymentStatus );

            }

            $order->save();

            if( $generateLog == true )
            {
                $this->generateLog( $order, $previousStatus );
            }

            $this->_coreRegistry->register('current_order', $order);

            $res = $this->_view->getLayout()->createBlock('Riki\ArReconciliation\Block\Adminhtml\PaymentStatus\PaymentStatus')->toHtml();

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

    /*generate chang log for payment status field*/
    private function generateLog( $or, $previousStatus )
    {
        $model = $this->_orderPaymentStatusLog;

        $model->setData( array(
            'user_id' => $this->_userId,
            'user_name' => $this->_userName,
            'order_id' => $or->getId(),
            'order_increment_id' => $or->getIncrementId(),
            'payment_status' => $or->getPaymentStatus(),
            'previous_status' => $previousStatus,
            'type' => OrderPaymentStatusLog::TYPE_MANUALLY,
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
     * get order by id
     *
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    protected function _getOrderById($orderId)
    {
        $order = $this->_orderRepository->get($orderId);

        if ($order->getId()) {
            return $order;
        }

        return false;
    }
}
