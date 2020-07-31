<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Reorder\Renderer\Action;

class AddDuplicateCreateOrderTabRule
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action $subject
     * @param $actionArray
     * @return array
     */
    public function beforeAddToActions(
        \Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action $subject,
        $actionArray
    ) {
        if (isset($actionArray['@']['href'])
            && strpos($actionArray['@']['href'], 'sales/order_create/reorder') !== false
        ) {
            $url = $actionArray['@']['href'];

            $actionArray['@']['href'] = 'javascript:void(0);';
            $actionArray['@']['onclick'] = 'handleUrlOnclickEvent(\'' . $url . '\')';
        }

        return [$actionArray];
    }
}