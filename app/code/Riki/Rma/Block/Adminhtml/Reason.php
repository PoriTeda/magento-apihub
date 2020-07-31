<?php
namespace Riki\Rma\Block\Adminhtml;

class Reason extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_reason';
        $this->_blockGroup = 'Riki_Rma';
        $this->_headerText = __('Manage Rma Reason');

        parent::_construct();

        if ($this->_isAllowedAction('Riki_Rma::reason_save')) {
            $this->buttonList->update('add', 'label', __('Add New Reason'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newAction');
    }
}