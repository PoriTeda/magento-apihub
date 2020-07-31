<?php

namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class MassDelete extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{

    /**
     * Delete one or more reason
     *
     * @return void
     */
    public function execute()
    {
        $reasonIds = $this->getRequest()->getParam('reason');
        if (!is_array($reasonIds)) {
            $this->messageManager->addError(__('Please select one or more reason.'));
        } else {
            try {
                foreach ($reasonIds as $reasonId) {
                    $reason = $this->_reasonFactory->create()->load(
                        $reasonId
                    );
                    $reason->setStatus(0)->save();
                }
                $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($reasonIds)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*');
    }

    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason_delete');
    }
}
