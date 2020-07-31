<?php

namespace Riki\Sales\Controller\Adminhtml\Order;

use Magento\Framework\Exception\LocalizedException;
use Riki\Rule\Model\CumulatedGift;

/**
 * Class Cancel
 * @package Riki\Sales\Controller\Adminhtml\Order
 */
class Cancel extends \Magento\Sales\Controller\Adminhtml\Order\Cancel
{
    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $_emailHelper;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_dataHelper;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $_preOrderHelper;
    /**
     * @var \Riki\EmailMarketing\Helper\Order
     */
    protected $_orderHelper;
    /**
     * @var \Riki\Rma\Api\ReasonRepositoryInterface
     */
    protected $_reasonRepository;

    /**
     * @var CumulatedGift
     */
    protected $_cumulatedGift;

    /**
     * @var \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $rikiAdminHelper;

    /**
     * Cancel constructor.
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
     * @param \Riki\Sales\Helper\Email $email
     * @param \Riki\Sales\Helper\Data $data
     * @param \Riki\Sales\Helper\Admin $rikiAdminHelper
     * @param \Riki\Preorder\Helper\Data $preOrder
     * @param \Riki\EmailMarketing\Helper\Order $orderHelper
     * @param \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository
     * @param CumulatedGift $cumulatedGift
     * @param \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender $orderSender
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
        \Riki\Sales\Helper\Email $email,
        \Riki\Sales\Helper\Data $data,
        \Riki\Sales\Helper\Admin $rikiAdminHelper,
        \Riki\Preorder\Helper\Data $preOrder,
        \Riki\EmailMarketing\Helper\Order $orderHelper,
        \Riki\Rma\Api\ReasonRepositoryInterface $reasonRepository,
        CumulatedGift $cumulatedGift,
        \Riki\SubscriptionEmail\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory,
            $resultLayoutFactory, $resultRawFactory, $orderManagement, $orderRepository, $logger
        );
        $this->_emailHelper = $email;
        $this->_dataHelper = $data;
        $this->_preOrderHelper = $preOrder;
        $this->_orderHelper = $orderHelper;
        $this->_reasonRepository = $reasonRepository;
        $this->_cumulatedGift = $cumulatedGift;
        $this->_orderSender = $orderSender;
        $this->rikiAdminHelper = $rikiAdminHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addError(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }

        $order = $this->_initOrder();

        if ($order && $this->canCancel($order)) {
            try {
                $this->cancel($order);
                $this->messageManager->addSuccess(__('The order has been canceled successfully.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('The order has been canceled unsuccessfully.'));
            }

            if ($this->getRequest()->getParam('allow_send_cancel_order_email')) {
                /**
                 * Send email confirm if The email does not send
                 */
                if (!$order->getEmailSent()) {
                    $this->_coreRegistry->register(
                        'send_mail_confirm_before_email_cancel',
                        $order->getEntityId()
                    );
                    $this->_orderSender->send($order, true);
                    $this->processSendMailCancelOrder($order);
                } else {
                    $this->processSendMailCancelOrder($order);
                }
            }

            return $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $order->getId()]
            );
        }

        return $resultRedirect->setPath('sales/*/');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function canCancel(\Magento\Sales\Model\Order $order)
    {
        if ($this->_dataHelper->validateOrderShip($order)) {
            $this->messageManager->addError(__('Can\'t cancel order placed after shipment.'));
            return false;
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return $this
     */
    protected function cancel(\Magento\Sales\Model\Order $order)
    {
        $reasonCancel = $this->getRequest()->getParam('reasoncancel');

        $this->orderManagement->cancel($order->getEntityId());

        $history = $order->addStatusHistoryComment($reasonCancel);
        $history->setIsVisibleOnFront(true);
        $history->setIsCustomerNotified(false);
        $history->save();

        $order->setCancelReason($reasonCancel);

        /**
         * Check cancellation promotion cumulative 3.1.1
         */
        $this->_cumulatedGift->cancel($order);

        return $this;
    }

    /**
     * Process send mail cancel order
     *
     * @param $order
     */
    public function processSendMailCancelOrder($order)
    {
        /* send mail cancel*/
        /*Controlled by Email Marketing*/
        if ($this->_dataHelper->getEnableEmail()) {
            try {
                $vars = $this->_orderHelper->getOrderVariables($order, 'spot_cancel_order');
                $emailCustomer = trim($order->getCustomerEmail());
                $isPreOrder = $this->_preOrderHelper->getOrderIsPreorderFlag($order);

                /** @var $helperMail \Riki\Sales\Helper\Email */
                $helperMail = $this->_emailHelper;

                if ($isPreOrder) {
                    $helperMail->sendMailCancelPreOrder($vars, $emailCustomer);
                } else {
                    $helperMail->sendMailCancelOrder($vars, $emailCustomer);
                }
                /* send mail to admin config for order use CVS payment method*/
                if ($this->_dataHelper->isCVSMethod($order)) {
                    $recipients = array_filter(explode(",", $this->_dataHelper->getReceiverEmail()), 'trim');
                    if (!empty($recipients)) {
                        $varsAdmin = [
                            'order' => $order,
                            'comment' => $order->getCancelReason(),
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
                $this->messageManager->addError(__('The order cancel email is not sent.'));
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @return bool
     */
    protected function isValidPostRequest()
    {
        $reason = $this->getRequest()->getParam('reasoncancel');

        if (!in_array($reason, $this->rikiAdminHelper->getOrderCancelReasons())) {
            return false;
        }

        return parent::isValidPostRequest();
    }

}