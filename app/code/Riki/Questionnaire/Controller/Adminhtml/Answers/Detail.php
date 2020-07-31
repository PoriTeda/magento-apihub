<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Answers;

/**
 * Class Detail
 * @package Riki\Questionnaire\Controller\Adminhtml\Answers
 */
class Detail extends AnswersAbstract
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::answers_view');
    }

    /**
     * View detail answer
     * 
     * @return $this|\Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('answer_id');
        
        $model = $this->_answersFactory->create();
        
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This answer no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        
        $data = $this->_session->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        
        $this->registry->register('answers', $model);
        $this->registry->register('current_answers', $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->addBreadcrumb(__('Detail Answer'), __('Detail Answer'));
        
        $resultPage->getConfig()->getTitle()->prepend(__('Detail Answer'));

        return $resultPage;
    }
}