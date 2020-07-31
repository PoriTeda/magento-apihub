<?php
namespace Riki\SubscriptionProfileDisengagement\Block\Adminhtml;
class Reason extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_reason';
        $this->_blockGroup = 'Riki_SubscriptionProfileDisengagement';
        $this->_headerText = __('Manage Reason');
        parent::_construct();
        if ($this->_isAllowedAction('Riki_SubscriptionProfileDisengagement::reason_save')) {
            $this->buttonList->update('add', 'label', __('Add New Reason'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    /**
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newAction');
    }
}