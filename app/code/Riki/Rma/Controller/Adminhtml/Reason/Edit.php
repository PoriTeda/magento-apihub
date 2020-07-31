<?php
namespace Riki\Rma\Controller\Adminhtml\Reason;

class Edit extends \Riki\Rma\Controller\Adminhtml\Reason
{
    const ADMIN_RESOURCE = 'Riki_Rma::reason_save';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            /** @var \Riki\Rma\Model\Reason $model */
            try {
                $model = $this->reasonRepository->getById($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addError(__('This reason no longer exists.'));
                return $this->_redirect('*/*/');
            }

            if (!$model->getId() || $model->getDeleted()) {
                $this->messageManager->addError(__('This reason no longer exists.'));
                return $this->_redirect('*/*/');
            }
        } else {
            $model = $this->reasonRepository->createFromArray();
        }

        if (is_null($this->registry->registry('_current_reason'))) {
            $this->registry->register('_current_reason', $model);
        }

        $result = $this->initPageResult();
        $result->setActiveMenu('Riki_Rma::reason');

        if ($model->getId()) {
            $breadcrumbTitle = __('Edit Rma Reason');
            $breadcrumbLabel = $breadcrumbTitle;
        } else {
            $breadcrumbTitle = __('New Rma Reason');
            $breadcrumbLabel = __('Create Rma Reason');
        }

        $result->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getCode() : __('New Rma Reason')
        );
        $result->addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

        // restore data
        $values = $this->_getSession()->getData('riki_rma_reason_form_data', true);
        if ($values) {
            $model->addData($values);
        }

        return $result;
    }
}