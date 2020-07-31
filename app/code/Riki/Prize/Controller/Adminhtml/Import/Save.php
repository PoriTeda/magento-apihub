<?php
namespace Riki\Prize\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * Prize importer model
     *
     * @var \Riki\Prize\Model\Prize\Import
     */
    protected $_prizeImport;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Prize\Model\Prize\Import $importer
    )
    {
        $this->_prizeImport = $importer;
        parent::__construct($context);
    }

    /**
     * Import prize from CSV action
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

            $importResult = $this->_prizeImport->doImport('csv_file');
            $resultBlock->addSuccess(__('Import successfully done: %1 records', $importResult['success']));
            return $resultLayout;
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }
}