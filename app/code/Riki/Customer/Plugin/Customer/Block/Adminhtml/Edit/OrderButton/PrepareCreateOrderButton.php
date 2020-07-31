<?php
namespace Riki\Customer\Plugin\Customer\Block\Adminhtml\Edit\OrderButton;

class PrepareCreateOrderButton
{
    /**
     * @param \Magento\Customer\Block\Adminhtml\Edit\OrderButton $subject
     * @param $result
     * @return mixed
     */
    public function afterGetButtonData(
        \Magento\Customer\Block\Adminhtml\Edit\OrderButton $subject,
        $result
    ) {

        if (count($result)) {
            $result['on_click'] = 'handleUrlOnclickEvent(\'' . $subject->getCreateOrderUrl() . '\')';
        }

        return $result;
    }
}