<?php
namespace Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair;

class Edit extends \Riki\FairAndSeasonalGift\Controller\Adminhtml\Fair
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

        $id = $this->getRequest()->getParam('fair_id');

        $model = $this->initModel();

        if ($id && !$model->getFairId()) {
            $this->messageManager->addError(__('This item not exists.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $this->initPage($resultPage)
            ->getConfig()->getTitle()->prepend($id ? $model->getName() : __('Add New Fair'));

        $values = $this->_getSession()->getData('riki_fair_form_data', true);

        if (!empty($values)) {
            $model->addData($values);
        }

        $this->registry->register('current_fair_form', $model);

        return $resultPage;
    }
}
