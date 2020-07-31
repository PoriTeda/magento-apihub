<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Answers;

/**
 * Class Delete
 * @package Riki\Questionnaire\Controller\Adminhtml\Answers
 */
class Delete extends AnswersAbstract
{
    /**
     * Delete enquete answers
     * 
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('answer_id');
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $model = $this->_answersFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('The questionnaire answers has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/detail', ['answer_id' => $id]);
            }
        }

        $this->messageManager->addError(__('This questionnaire answers no longer exists.'));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Allowed
     * 
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::answersdelete');
    }
}
