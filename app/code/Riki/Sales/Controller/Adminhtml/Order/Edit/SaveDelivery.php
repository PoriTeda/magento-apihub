<?php
namespace Riki\Sales\Controller\Adminhtml\Order\Edit;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class SaveDelivery extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $_rikiSalesAdminHelper;

    /**
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
     * SaveDelivery constructor.
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
     * @param \Riki\Sales\Helper\Admin $rikiSalesAdminHelper
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
        \Riki\Sales\Helper\Admin $rikiSalesAdminHelper,
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
        $this->_rikiSalesAdminHelper = $rikiSalesAdminHelper;
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

            $connection = $this->_rikiSalesAdminHelper->getConnection();

            try {

                $connection->beginTransaction();

                $data = $this->getRequest()->getPost('order', []);

                $newAddressId = $this->getRequest()->getPost('new_address_selected', 0);

                $newAddress = $this->_rikiSalesAddressHelper->initShippingAddressFromCustomerAddress($order, $newAddressId);
                $this->_rikiSalesAddressHelper->changeShippingAddressForSingleOrder($order, $newAddress);

                $this->_rikiSalesAdminHelper->updateOrderItemDeliveryInfo($order, $data);

                $this->_eventManager->dispatch('adminhtml_sales_order_update_delivery', [
                    'order' => $order,
                    'update_data' => $data,
                ]);

                $order->addStatusHistoryComment(__('Delivery information was updated by %1', $this->authSession->getUser()->getUserName()));
                $this->orderRepository->save($order);

                $connection->commit();

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $connection->rollBack();
                return $this->_generateErrorResult(['error' => true, 'message' => $e->getMessage()]);
            } catch (\Exception $e) {
                $connection->rollBack();
                return $this->_generateErrorResult(['error' => true, 'message' => __('We cannot save order delivery.')]);
            }

            try{
                $this->_emailHelper->sendMailOrderChange($order->getEntityId(),'','SaveDelivery-Admin Controller');
            }catch (\Exception $e){
                $this->logger->critical($e);
            }

            return $this->resultPageFactory->create();
        }
        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }

    /**
     *
     */
    protected function _generateErrorResult(array $response){
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions_edit');
    }
}
