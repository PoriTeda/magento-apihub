<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;

class Index extends QuestionsAbstract
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::questionnaire');
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage questionnaires'));

        return $resultPage;
    }
}