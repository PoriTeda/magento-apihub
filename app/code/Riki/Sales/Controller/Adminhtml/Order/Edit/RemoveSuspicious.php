<?php

namespace Riki\Sales\Controller\Adminhtml\Order\Edit;

use Magento\Sales\Model\Order as MageOrder;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatusResourceModel;

class RemoveSuspicious extends \Magento\Sales\Controller\Adminhtml\Order
{
    /**
     * @var \Riki\Sales\Helper\Order
     */
    protected $_orderHelper;

    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker
     */
    protected $duoMachineChecker;

    /**
     * RemoveSuspicious constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Sales\Helper\Order $orderHelper
     * @param \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Sales\Helper\Order $orderHelper,
        \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
    ) {
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
        $this->_orderHelper = $orderHelper;
        $this->duoMachineChecker = $duoMachineChecker;
    }

    /**
     * remove suspicious order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $order = $this->_initOrder();

        if ($order) {
            try {
                if ($order->getStatus() != \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t remove suspicious order.'));
                }

                $this->removeSuspiciousStatus($order);

                $this->messageManager->addSuccess(__('You removed suspicious the order from suspicious status.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('The order was not on suspicious.'));
            }

            $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);

            return $resultRedirect;
        }
        $resultRedirect->setPath('sales/order/');
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions_edit');
    }

    /**
     * Remove suspicious status for order
     *      Change order status to
     *          1. pending_cvs_payment for order which payment method is cvspayment
     *          2. waiting_for_shipping(not_shipped) for order which payment method is not cvs payment
     *          3. pending_crd_review for free of charge, waiting point approved order -> highest priority
     * @param $order
     */
    public function removeSuspiciousStatus($order)
    {
        /*get order payment method*/
        $paymentMethod = $this->_orderHelper->getOrderPaymentMethod($order);

        /*change order status to pending cvs for order which payment method is cvs payment*/
        switch ($paymentMethod) {
            case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                /*change order status to pending cvs for order which payment method is cvs payment*/
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_CVS;
                $newState = MageOrder::STATE_NEW;
                break;
            case \Riki\NpAtobarai\Model\Payment\NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                /*change order status to pending np for order which payment method is Np Atobarai payment*/
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_PENDING_NP;
                $newState = MageOrder::STATE_NEW;
                break;
            default:
                /*change status to not shipped for remaining order*/
                $newStatus = OrderStatusResourceModel::STATUS_ORDER_NOT_SHIPPED;
                $newState = MageOrder::STATE_PROCESSING;
                break;
        }

        // PENDING_FOR_MACHINE case
        // If this order has order item or oos item is duo machine, change status to PENDING_FOR_MACHINE
        if ($this->duoMachineChecker->isOrderHasFreeDuoMachine($order) ||
            $this->duoMachineChecker->isOrderHasOosItemDuoMachine($order)
        ) {
            $newStatus = \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE;
            $newState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        }

        /*flag to check this order need to approved by call center*/
        $crdReview = $this->_orderHelper->isCrdReviewOrder($order);

        /*flag to check this order need to approved earn point*/
        $isWaitingPointApprove = $this->_orderHelper->isWaitingPointApprovalOrder($order);

        /*change status to crd review for order that need to approved to earn point by call center*/
        if ($isWaitingPointApprove) {
            $crdReview = true;

            /*addition process for order that need to approved for earn point - send request approval email*/
            $this->_orderHelper->requestApprovalEarnPoint($order);
        }

        /*change status to pending crd review for order that need to approved by call center*/
        if ($crdReview) {
            $newStatus = \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW;
            $newState = \Magento\Sales\Model\Order::STATE_HOLDED;
        }

        /*change order data*/
        $order->setState($newState);
        $order->setStatus($newStatus);

        /*add status history comment*/
        $order->setIsNotified(false);

        $order->addStatusHistoryComment(
            __('Remove suspicious'),
            false
        );

        $order->save();
    }
}
