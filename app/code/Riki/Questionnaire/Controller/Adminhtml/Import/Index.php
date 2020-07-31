<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Import;
use Magento\Framework\Controller\ResultFactory;
class Index extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::answers');
    }




    public function execute()
    {
        /**
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_Questionnaire::importQuestionaire');
        $resultPage->addBreadcrumb(__('Questionnaire'), __('Questionnaire'));
        $resultPage->getConfig()->getTitle()->prepend(__('Questionnaire Import'));
        return $resultPage;
    }
}