<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

/**
 * Class Delete
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
class Delete extends AbstractCampaign
{

    /**
     * Implement the action name
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('campaign_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->campaignFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The campaign has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->logger->critical($e);
                return $resultRedirect->setPath('*/*/edit', ['campaign_id' => $id]);
            }
        }
        $this->messageManager->addError(__('This campaign no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check ACL permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE_DELETE);
    }
}
