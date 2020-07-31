<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;
/**
 * Class Edit
 * @package Riki\Questionnaire\Controller\Adminhtml\Questions
 */
class Edit extends QuestionsAbstract
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::save');
    }

    /**
     * Edit Questionnaire
     *
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('enquete_id');

        /** @var \Riki\Questionnaire\Model\Questionnaire $model */
        $model = $this->questionnaireFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This questionnaire no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        
        $this->registry->register('questionnaire', $model);
        $this->registry->register('current_questionnaire', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Questionnaire') : __('New Questionnaire'),
            $id ? __('Edit Questionnaire') : __('New Questionnaire')
        );
        
        $resultPage->getConfig()->getTitle()->prepend(
                $model->getId() ? __('Edit Questionnaire') : __('New Questionnaire')
            );

        return $resultPage;
    }
}