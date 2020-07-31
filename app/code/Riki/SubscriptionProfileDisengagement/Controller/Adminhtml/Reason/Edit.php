<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class Edit extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{

    /**
     * edit ram reason
     */
    public function execute()
    {
        $model = $this->_reasonFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $model->load($id);
        }

        $this->_coreRegistry->register('_current_reason', $model);
        $this->_coreRegistry->register('_current_reason_id', $id);

        $this->_view->loadLayout();
        $this->_setActiveMenu('SubscriptionProfileDisengagement::reason');

        if ($model->getId()) {
            $breadcrumbTitle = __('Edit Subscription Profile Disengagement Reason  #%1', $id);
            $breadcrumbLabel = $breadcrumbTitle;
        } else {
            $breadcrumbTitle = __('New Subscription Profile Disengagement Reason');
            $breadcrumbLabel = __('Create Subscription Profile Disengagement Reason');
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Subscription Profile Disengagement Reason'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit Subscription Profile Disengagement Reason  #%1', $id) : __('New Subscription Profile Disengagement Reason')
        );

        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

        // restore data
        $values = $this->_getSession()->getData('riki_spdisengagement_reason_form_data', true);
        if ($values) {
            $model->addData($values);
        }

        $this->_view->renderLayout();
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason_save');
    }
}