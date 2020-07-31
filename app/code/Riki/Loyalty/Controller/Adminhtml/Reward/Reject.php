<?php

namespace Riki\Loyalty\Controller\Adminhtml\Reward;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Riki\Loyalty\Model\ResourceModel\Reward;
use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;

class Reject extends \Riki\Loyalty\Controller\Adminhtml\Reward
{
    /**
     * Reject the shopping point in approval
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = $this->_request->getParam('order_id');
        $redirectUrl = $this->getUrl('sales/order/view', ['order_id' => $orderId]);
        try {
            /** @var Order $order */
            $order = $this->_orderRepository->get($orderId);
            /** @var Reward $resourceModel */
            $resourceModel = $this->_rewardResourceFactory->create();
            $deleted = $resourceModel->cancelRewardPoint($order->getIncrementId());
            if ($deleted) {
                $this->messageManager->addSuccess(__(
                    'Successfully reject pending point of order %1',
                    $order->getIncrementId()
                ));

                if ($order->getStatus() == OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW) {
                    $order->setStatus(OrderStatus::STATUS_ORDER_CRD_FEEDBACK);
                    $order->addStatusToHistory(
                        OrderStatus::STATUS_ORDER_CRD_FEEDBACK,
                        __('Shopping point was rejected')
                    );
                } else {
                    $order->addStatusToHistory($order->getStatus(), __('Shopping point was rejected'));
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