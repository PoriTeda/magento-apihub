<?php
namespace Riki\Sales\Plugin;

class ButtonToolbarBefore
{
    private $checked = false;

    /**
     * @param \Magento\Backend\Block\Widget\Button\Toolbar $subject
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {

        if ($this->checked) {
            return [$context, $buttonList];
        }

        $this->checked = true;

        if ($context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            $buttonList->add(
                'create_free_order',
                [
                    'label' => __('Create Free Order'),
                    'onclick' => 'handleUrlOnclickEvent(\'' . $context->getUrl('riki_sales/order_create/freeOrder', ['order_id'    =>  $context->getOrderId()]) . '\')',
                    'class' => 'reorder'
                ]
            );

            /*get order info*/
            $order = $context->getOrder();

            /*add remove suspicious button for order which status is suspicious*/
            if (!empty($order) && $order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_SUSPICIOUS) {

                $buttonList->add(
                    'remove_suspicious',
                    [
                        'label' => __('Remove Suspicious'),
                        'onclick' => 'setLocation(\'' . $context->getUrl('riki_sales/order_edit/removeSuspicious', ['order_id'    =>  $context->getOrderId()]) . '\')',
                        'class' => 'reorder'
                    ]
                );
            }

            $buttonList->update(
                'order_reorder',
                'onclick',
                'handleUrlOnclickEvent(\'' . $context->getReorderUrl() .'\')'
            );
        }

        return [$context, $buttonList];
    }
}
