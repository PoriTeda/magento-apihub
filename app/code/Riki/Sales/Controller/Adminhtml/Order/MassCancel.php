<?php
namespace Riki\Sales\Controller\Adminhtml\Order;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Exception\LocalizedException;
/**
 * Class MassCancel
 * @package Riki\Sales\Controller\Adminhtml\Order
 */
class MassCancel extends \Magento\Sales\Controller\Adminhtml\Order\MassCancel
{
    protected $_logger;
    protected $_emailHelper;
    protected $_dataHelper;
    protected $_preOrderHelper;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Sales\Helper\Email $email,
        \Riki\Sales\Helper\Data $data,
        \Riki\Preorder\Helper\Data $preOrder
    ) {
        parent::__construct($context, $filter, $collectionFactory);
        $this->_logger = $logger;
        $this->_emailHelper = $email;
        $this->_dataHelper = $data;
        $this->_preOrderHelper = $preOrder;
    }

    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        $countOrderExportedWarehouse = 0;
        $countOrderIsInvoice = 0;
        
        /** @var $helperData \Riki\Sales\Helper\Data */
        $helperData = $this->_dataHelper;

        /** @var $helperMail \Riki\Sales\Helper\Email */
        $helperMail = $this->_emailHelper;

        $reasonCancel = $this->getRequest()->getParam('reasoncancel');
        
        foreach ($collection->getItems() as $order) {
            if (!$order->canCancel()) {
                continue;
            }
                /* Check order not export to warehouse*/
                $status = $helperData->checkStatusOrderCancel($order);

                if ($status) {
                    try {

                        $isPreorder = $this->_preOrderHelper->getOrderIsPreorderFlag($order);

                        $order->cancel();
                        $order->save();
                        $countCancelOrder++;

                        if (isset($reasonCancel)) {
                            $history = $order->addStatusHistoryComment($reasonCancel);
                            $history->setIsVisibleOnFront(true);
                            $history->setIsCustomerNotified(false);
                            $history->save();
                        }

                        /* send mail cancel*/
                        $vars = $helperData->prepareDataCancelOrderTemplate($order);
                        $emailCustomer = trim($order->getCustomerEmail());

                        if($isPreorder){
                            $helperMail->sendMailCancelPreOrder($vars, $emailCustomer);
                        } else {
                            $helperMail->sendMailCancelOrder($vars, $emailCustomer);
                        }

                        /* send mail to admin config for order use CVS payment method*/
                        if ($helperData->isCVSMethod($order)) {
                            $recipients = array_filter(explode(",", $helperData->getReceiverEmail()), 'trim');
                            if (!empty($recipients)) {
                                $varsAdmin = [
                                    'order' => $order,
                                    'comment' => $reasonCancel,
                                    'store' => $order->getStore()
                                ];

                                foreach ($recipients as $email) {
                                    $helperMail->sendMailCancelOrder($varsAdmin, $email, true);
                                }
                            }
                        }
                        /* end send mail to admin config for order use CVS payment method*/

                        /* end send mail cancel*/
                    } catch (LocalizedException $e) {
                        $this->messageManager->addError($e->getMessage());
                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                    }
                } else {
                    $countOrderExportedWarehouse++;
                }
            }
        

        $countNonCancelOrder = $collection->getSize() - $countCancelOrder;

        $this->addMessage($countNonCancelOrder, $countCancelOrder, $countOrderExportedWarehouse, $countOrderIsInvoice);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }

    /**
     * @param $countNonCancelOrder
     * @param $countCancelOrder
     * @param $countOrderExportedWarehouse
     * @param $countOrderIsInvoice
     */
    public function addMessage($countNonCancelOrder, $countCancelOrder, $countOrderExportedWarehouse, $countOrderIsInvoice){
        if ($countNonCancelOrder && $countCancelOrder) {

            if ($countOrderExportedWarehouse) {
                $this->messageManager->addError(__('%1 order(s) exported to warehouse.', $countOrderExportedWarehouse));
            }
            if ($countOrderIsInvoice) {
                $this->messageManager->addError(__('%1 order(s) can\'t cancel order placed with Invoices-based method', $countOrderIsInvoice));
            }
            if (!$countOrderExportedWarehouse && !$countOrderIsInvoice) {
                $this->messageManager->addError(__('%1 order(s) cannot be canceled.', $countNonCancelOrder));
            }

        } elseif ($countNonCancelOrder) {

            if ($countOrderExportedWarehouse) {
                $this->messageManager->addError(__('%1 order(s) exported to warehouse.', $countOrderExportedWarehouse));
            }
            if ($countOrderIsInvoice) {
                $this->messageManager->addError(__('%1 order(s) can\'t cancel order placed with Invoices-based method', $countOrderIsInvoice));
            }
            if (!$countOrderExportedWarehouse && !$countOrderIsInvoice) {
                $this->messageManager->addError(__('You cannot cancel the order(s).'));
            }
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccess(__('We canceled %1 order(s).', $countCancelOrder));
        }
    }

    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed(){
        return $this->_authorization->isAllowed('Magento_Sales::cancel');
    }
}
