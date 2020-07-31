<?php
namespace Riki\Fraud\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;

class Approve extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Riki\Fraud\Helper\SuspectedFraud
     */
    protected $_suspectedHelper;

    /**
     * Approve constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Fraud\Helper\SuspectedFraud $suspectedHelper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Fraud\Helper\SuspectedFraud $suspectedHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_orderRepository = $orderRepository;
        $this->_logger = $logger;
        $this->_suspectedHelper = $suspectedHelper;

        parent::__construct($context);
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('order_id');

            $order = $this->getOrderById($id);

            $order->setData('fraud_status', \Riki\Fraud\Model\Score::STATUS_APPROVE);

            $order->save();

            $this->_suspectedHelper->approvedOrder($id);

            $this->_coreRegistry->register('current_order', $order);

            $res = $this->_view->getLayout()->createBlock('Riki\Fraud\Block\Adminhtml\Order\View\Tab')->toHtml();

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
     * @param $orderId
     * @return bool
     */
    public function getOrderById($orderId)
    {
        try {
            return $this->_orderRepository->get($orderId);
        } catch (\Exception $e){
            $this->_logger->critical( $e->getMessage() );
            return false;
        }
    }
}