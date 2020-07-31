<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

/**
 * Class Edit
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
class Edit extends AbstractCampaign
{
    /**
     * Implement edit action
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('campaign_id');
        $model = $this->campaignFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This campaign no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        $this->registry->register('subscription_campaign', $model);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(
            $id ? $model->getCampaignName() : __('New Multiple Category Campaign'),
            $id ? $model->getCampaignName() : __('New Multiple Category Campaign')
        );

        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getCampaignName() : __('New Multiple Category Campaign')
        );
        return $resultPage;
    }

    /**
     * Check ACL permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE_EDIT);
    }
}
