<?php

namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order;

use Riki\NpAtobarai\Model\Payment\NpAtobarai;

class View
{
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * View constructor.
     *
     * @param \Riki\Sales\Helper\Data $dataHelper
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     */
    public function __construct(
        \Riki\Sales\Helper\Data $dataHelper,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement
    ) {
        $this->dataHelper = $dataHelper;
        $this->rewardManagement = $rewardManagement;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $subject
     * @param \Magento\Sales\Block\Adminhtml\Order\View $result
     *
     * @return \Magento\Sales\Block\Adminhtml\Order\View
     */
    public function afterSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\View $subject,
        \Magento\Sales\Block\Adminhtml\Order\View $result
    ) {
        $order = $subject->getOrder();
        $eanPoint = $order->getCanShowPointApproval();
        //Checkpoint expiration
        if ($order->getUsedPoint() && $order->getUsedPoint() > 0) {
            $pointValidate = $this->rewardManagement->checkPointExpiration($order);
            if (is_array($pointValidate)
                && !$pointValidate['error']
                && $pointValidate['data']->return[0] == \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint::CODE_EXPIRATION
            ) {
                $dataAtribute  = [
                    'url' => $subject->getCancelUrl(),
                    'message_point' => $order->getUsedPoint()
                ];
                $subject->updateButton(
                    'order_cancel',
                    'data_attribute',
                    $dataAtribute
                );
            }
        }
        if ($eanPoint) {
            $paramLinkButton = [
                'urlApprove' =>
                    $subject->getUrl(
                        'riki_loyalty/reward/approve',
                        ['order_id' => $order->getId()]
                    ),
                'urlReject' =>
                    $subject->getUrl(
                        'riki_loyalty/reward/reject',
                        ['order_id' => $order->getId()]
                    )
            ];

            $subject->updateButton(
                'order_unhold',
                'data_attribute',
                $paramLinkButton
            );
        }

        // Order status to cancel
        $status = $this->dataHelper->checkStatusOrderCancel($order);
        if (!$status) {
            $subject->removeButton('order_cancel');
        }

        //NP-Atobarai order not allow to edit via back office. Please hide "edit" action for NP-Atobarai order.
        $payment = $order->getPayment();
        if ($payment && $payment->getMethod() == NpAtobarai::PAYMENT_METHOD_NP_ATOBARAI_CODE) {
            $subject->removeButton('order_edit');
        }

        return $result;
    }

    /**
     * Plugin to update html class for cancel button
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View $subject
     */
    public function beforeSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\View $subject
    ) {
        $order = $subject->getOrder();
        if ($order->canCancel()) {
            if ($this->dataHelper->isOrderInProcessingAndExported($order)) {
                $subject->updateButton('order_cancel', 'class', 'cancel order_exported');
            }
        }
    }
}
