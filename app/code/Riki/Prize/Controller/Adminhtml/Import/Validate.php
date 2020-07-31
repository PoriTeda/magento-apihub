<?php
namespace Riki\Prize\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * Prize importer model
     *
     * @var \Riki\Prize\Model\Prize\Import
     */
    protected $_prizeImport;

    /**
     * Validate constructor.
     * 
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Prize\Model\Prize\Import $importer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Prize\Model\Prize\Import $importer
    )
    {
        $this->_prizeImport = $importer;
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
            $messages = $this->_prizeImport->validateSource('csv_file');
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
