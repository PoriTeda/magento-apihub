<?php
namespace Riki\Fraud\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Fraud\Model\Rule\Import
     */
    protected $_ruleImport;

    /**
     * Validate constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Fraud\Model\Rule\Import $importer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Fraud\Model\Rule\Import $importer
    )
    {
        $this->_ruleImport = $importer;
        parent::__construct($context);
    }

    /**
     * Validate uploaded files action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $dataPost = $this->getRequest()->getPostValue();
        if ($dataPost) {
            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            /** @var $resultBlock ImportResultBlock */
            $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');
            $resultBlock->addAction(
                'show',
                'import_validation_container'
            );
            $messages = $this->_ruleImport->validateSource('yml_file');
            foreach ($messages as $type => $arrMsg) {
                $method = $type == 'error' ? 'addError' : 'addSuccess';
                foreach ($arrMsg as $msg) {
                    $resultBlock->{$method}($msg, true);
                }
            }
            return $resultLayout;
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/edit');
        return $resultRedirect;
    }
}
