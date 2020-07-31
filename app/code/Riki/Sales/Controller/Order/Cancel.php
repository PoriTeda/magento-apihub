<?php
namespace Riki\Sales\Controller\Order;

use Magento\Sales\Controller\OrderInterface;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;
use Riki\Rule\Model\CumulatedGift;

/**
 * Class Cancel
 * @package Riki\Sales\Controller\Order
 */
class Cancel extends \Magento\Framework\App\Action\Action implements OrderInterface
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_salesHelper;
    /**
     * @var \Riki\Sales\Helper\Email
     */
    protected $_salesHelperMail;
    /**
     * @var \Riki\EmailMarketing\Helper\Order
     */
    protected $_orderHelper;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $_preOrderHelper;

    /**
     * @var CumulatedGift
     */
    protected $_cumulatedGift;

    /**
     * Cancel constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param \Riki\Sales\Helper\Data $salesHelper
     * @param \Riki\Sales\Helper\Email $salesHelperMail
     * @param LoggerInterface $logger
     * @param \Riki\EmailMarketing\Helper\Order $orderHelper
     * @param \Riki\Preorder\Helper\Data $preOrder
     * @param CumulatedGift $cumulatedGift
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        \Riki\Sales\Helper\Data $salesHelper,
        \Riki\Sales\Helper\Email $salesHelperMail,
        LoggerInterface $logger,
        \Riki\EmailMarketing\Helper\Order $orderHelper,
        \Riki\Preorder\Helper\Data $preOrder,
        CumulatedGift $cumulatedGift
    ) {
        $this->_cumulatedGift = $cumulatedGift;
        $this->_coreRegistry = $registry;
        $this->orderRepository = $orderRepository;
        $this->orderManagement = $orderManagement;
        $this->logger = $logger;
        $this->_salesHelper = $salesHelper;
        $this->_salesHelperMail = $salesHelperMail;
        $this->_orderHelper = $orderHelper;
        $this->_preOrderHelper = $preOrder;
        parent::__construct($context);
    }

    /**
     * Initialize order model instance
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        } catch (InputException $e) {
            $this->messageManager->addError(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        return $order;
    }

    /**
     * Customer cancel order
     * 
     * @return $this
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        $order = $this->_initOrder();

        if ($order) {
            /** @var $helperData \Riki\Sales\Helper\Data */
            $helperData = $this->_salesHelper;

                $status = $helperData->checkStatusOrderCancel($order);
                if ($order->getData('riki_type') != 'SUBSCRIPTION') {
                    if ($status) {
                        try {
                            $order->addStatusHistoryComment(__('Cancel order successfully from frontend.'), \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED);
                            $this->orderManagement->cancel($order->getEntityId());

                            /**
                             * Check cancellation promotion cumulative 3.1.1
                             */
                            $this->_cumulatedGift->cancel($order);
                            if ($helperData->getEnableEmail()){
                                /* send mail cancel*/
                                /*Controlled by Email Marketing*/
                                $vars = $this->_orderHelper->getOrderVariables($order,'spot_cancel_order');
                                $emailCustomer = trim($order->getCustomerEmail());
                                $isPreOrder = $this->_preOrderHelper->getOrderIsPreorderFlag($order);

                                /** @var $helperMail \Riki\Sales\Helper\Email */
                                $helperMail = $this->_salesHelperMail;

                                if($isPreOrder){
                                    $helperMail->sendMailCancelPreOrder($vars, $emailCustomer);
                                } else {
                                    $helperMail->sendMailCancelOrder($vars, $emailCustomer);
                                }
                            }



                            /* end send mail to admin config for order use CVS payment method*/
                            /* end send mail cancel*/
                            $this->messageManager->addSuccess(__('You canceled the order.'));
                        } catch (\Magento\Framework\Exception\LocalizedException $e) {
                            $this->messageManager->addError($e->getMessage());
                        } catch (\Exception $e) {
                            $this->messageManager->addError(__('You have not canceled the item.'));
                            $this->logger->critical($e);
                            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
                        }
                    } else {
                        $this->messageManager->addError(__('You have not canceled the item.'));
                        $this->messageManager->addError(__('The order exported to warehouse.'));
                        return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
                    }
                } else {
                    $this->messageManager->addError(__('You have not canceled the item.'));
                    $this->messageManager->addError(__('The order is order subscription.'));
                    return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
                }



        }
        return $resultRedirect->setPath('sales/order/history/');
    }

}