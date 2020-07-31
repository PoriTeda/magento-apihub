<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Questions;
/**
 * Class NewAction
 * @package Riki\Questionnaire\Controller\Adminhtml\Questions
 */
class NewAction extends QuestionsAbstract
{

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::new');
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->_forward('edit');
        
    }
}