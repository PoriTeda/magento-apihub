<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Answers;

/**
 * Class Index
 * @package Riki\Questionnaire\Controller\Adminhtml\Answers
 */
class Index extends AnswersAbstract
{
    /**
     * @return bool
     */
     protected function _isAllowed()
     {
         return $this->_authorization->isAllowed('Riki_Questionnaire::answers');
     }

    /**
     * Index list
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage questionnaire answers'));

        return $resultPage;
    }
}