<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order as MageOrder;
use Riki\NpAtobarai\Model\Payment\NpAtobarai;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatusResourceModel;
use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;

class Approve extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    /**
     * @var \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker
     */
    protected $duoMachineChecker;

    /**
     * Approve constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Loyalty\Model\RewardFactory $rewardFactory
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardResourceFactory
     * @param \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $consumerDb
     * @param \Riki\Loyalty\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardResourceFactory,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $consumerDb,
        \Riki\Loyalty\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Psr\Log\LoggerInterface $logger,
        \Riki\SubscriptionMachine\Model\MonthlyFee\DouMachineChecker $duoMachineChecker
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $scopeConfig,
            $resultLayoutFactory,
            $resultJsonFactory,
            $rewardFactory,
            $rewardResourceFactory,
            $consumerDb,
            $helper,
            $customerRepository,
            $orderRepository,
            $logger
        );
        $this->duoMachineChecker = $duoMachineChecker;
    }

    /**
     * Reject the shopping point in approval
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->_request->getParam('order_id');
        $redirectUrl = $this->getUrl('sales/order/view', ['order_id' => $orderId]);
        try {
            /** @var MageOrder $order */
            $order = $this->_orderRepository->get($orderId);
            /** @var \Riki\Loyalty\Model\ResourceModel\Reward $resourceModel */
            $resourceModel = $this->_rewardResourceFactory->create();
            $approved = $resourceModel->approveRewardPoint($order->getIncrementId());
            if ($approved) {
                $this->messageManager->addSuccess(__(
                    'Successfully approve pending point for order %1',
                    $order->getIncrementId()
                ));

                $paymentMethod = $order->getPayment()->getMethod();
                if ($order->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW) {
                    switch ($paymentMethod) {
                        case \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE:
                            $status = OrderStatus::STATUS_ORDER_PENDING_CVS;
                            $state = MageOrder::STATE_NEW;
                            break;
                        case NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE:
                            /*change order status to pending np for order which payment method is Np Atobarai payment*/
                            $status = OrderStatusResourceModel::STATUS_ORDER_PENDING_NP;
                            $state = MageOrder::STATE_NEW;
                            break;
                        default:
                            $state = MageOrder::STATE_PROCESSING;
                            $status = OrderStatus::STATUS_ORDER_NOT_SHIPPED;
                    }

                    // PENDING_FOR_MACHINE case
                    // If this order has order item or oos item is duo machine, change status to PENDING_FOR_MACHINE
                    if ($this->duoMachineChecker->isOrderHasFreeDuoMachine($order) ||
                        $this->duoMachineChecker->isOrderHasOosItemDuoMachine($order)
                    ) {
                        $status = \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE;
                        $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                    }

                    $order->setState($state);
                    $order->addStatusToHistory($status, __('Shopping point was approved'));
                } else {
                    $order->addStatusToHistory($order->getStatus(), __('Shopping point was approved'));
                }
                $this->_orderRepository->save($order);
            } else {
                throw new LocalizedException(__(
                    'Order %1 has not shopping point in pending approval',
                    $order->getIncrementId()
                ));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('An error occurs.'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($redirectUrl);
        return $resultRedirect;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Loyalty::approve_point');
    }
}
