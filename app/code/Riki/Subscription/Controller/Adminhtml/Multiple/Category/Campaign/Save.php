<?php
namespace Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign;

/**
 * Class Save
 * @package Riki\Subscription\Controller\Adminhtml\Multiple\Category\Campaign
 */
class Save extends AbstractCampaign
{
    /**
     * Implement Save action
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if data sent
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('campaign_id');
            $campaignModel = $this->campaignFactory->create();
            if ($id) {
                try {
                    $campaignModel->load($id);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
            if (!$campaignModel->getCampaignId() && $id) {
                $this->messageManager->addError(__('This campaign no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            try {
                $campaignModel->addData($data)->save();
                // display success message
                $this->messageManager->addSuccess(__('You saved the campaign.'));
                // clear previously saved data from session
                $this->_session->setFormData(false);
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // save data in session
                $this->_session->setFormData($data);
                $this->logger->critical($e);
            }
            return $redirectBack?
                $resultRedirect->setPath('*/*/edit', [
                    'campaign_id' => $campaignModel->getCampaignId()
                ])
                : $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addError('No data to save');
            return $resultRedirect->setPath('*/*/');
        }
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
