<?php

namespace Riki\SerialCode\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Magento\Backend\App\Action
{
    /**
     * Prize importer model
     *
     * @var \Riki\SerialCode\Model\SerialCode\Import
     */
    protected $_serialCodeImport;

    /**
     * Validate constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\SerialCode\Model\SerialCode\Import $importer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\SerialCode\Model\SerialCode\Import $importer
    )
    {
        $this->_serialCodeImport = $importer;
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
            $messages = $this->_serialCodeImport->validateSource('csv_file');
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
