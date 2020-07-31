<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;
/**
 * Class Delete
 * @package Riki\Questionnaire\Controller\Adminhtml\Questions
 */
class Delete extends QuestionsAbstract
{
    /**
     * Delete enquete
     * 
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('enquete_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->questionnaireFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The questionnaire has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['enquete_id' => $id]);
            }
        }

        $this->messageManager->addError(__('This questionnaire no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::delete');
    }
}
