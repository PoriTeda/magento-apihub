<?php
namespace Riki\Sales\Controller\Adminhtml\Order\Edit;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class UpdateShippingAddress extends \Magento\Sales\Controller\Adminhtml\Order
{

    /**\
     * @var \Riki\Sales\Helper\Address
     */
    protected $_rikiSalesAddressHelper;

    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $_emailHelper;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * UpdateShippingAddress constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param \Riki\Sales\Helper\Address $rikiSalesAddressHelper
     * @param \Riki\Sales\Helper\Email $emailHelper
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Riki\Sales\Helper\Address $rikiSalesAddressHelper,
        \Riki\Sales\Helper\Email $emailHelper,
        \Magento\Backend\Model\Auth\Session $authSession
    ){
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
        $this->_emailHelper = $emailHelper;
        $this->_rikiSalesAddressHelper = $rikiSalesAddressHelper;
        $this->authSession = $authSession;
    }

    /**
     * Save delivery action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order = $this->_initOrder();

        if ($order) {
            try {
                $data = $this->getRequest()->getPost('address', []);

                $this->_rikiSalesAddressHelper->updateOrderShippingAddresses($order, $data, $this->authSession->getUser()->getUserName());

                $this->_eventManager->dispatch('adminhtml_sales_order_update_shipping_address', [
                    'order' => $order,
                    'update_data' => $data,
                ]);
                $this->_emailHelper->sendMailOrderChange($order->getEntityId(),'','UpdateShippingAddress-Admin Controller');
                return $this->resultPageFactory->create();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $response = ['error' => true, 'message' => $e->getMessage()];
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $response = ['error' => true, 'message' => __('We cannot change shipping address.')];
            }
            if (is_array($response)) {
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($response);
                return $resultJson;
            }
        }

        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions_edit');
    }
}
