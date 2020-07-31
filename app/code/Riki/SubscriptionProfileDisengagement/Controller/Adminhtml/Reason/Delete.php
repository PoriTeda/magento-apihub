<?php

namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

class Delete extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{
    /**
     * Delete rma reason
     *
     * @return void
     */
    public function execute()
    {
        $rma = $this->_reasonFactory->create()->load(
            $this->getRequest()->getParam('id')
        );
        if ($rma->getId()) {
            try {
                $rma->setStatus(0)->save();
                $this->messageManager->addSuccess(__('The return has been deleted.'));
                $this->_getSession()->setFormData(false);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t delete this reason right now.'));
            }
        }
        $this->_redirect('*/*');
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason_delete');
    }
}
