<?php

namespace Riki\SerialCode\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Serial code importer model
     *
     * @var \Riki\SerialCode\Model\SerialCode\Import
     */
    protected $_serialCodeImport;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\SerialCode\Model\SerialCode\Import $importer
    )
    {
        $this->_serialCodeImport = $importer;
        parent::__construct($context);
    }

    /**
     * Import serial code from CSV action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');
            $resultBlock
                ->addAction('show', 'import_validation_container')
                ->addAction('innerHTML', 'import_validation_container_header', __('Status'))
                ->addAction('hide', ['edit_form', 'upload_button', 'messages']);

            $importResult = $this->_serialCodeImport->doImport('csv_file');
            $resultBlock->addSuccess(__('Import successfully done: %1 records', $importResult));
            return $resultLayout;
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SerialCode::serial_code_import');
    }

}