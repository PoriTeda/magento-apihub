<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;

use Magento\Framework\Controller\ResultFactory;

class Process extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Customer\Model\Shosha\Import
     */
    protected $_shoshaImport;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Customer\Model\Shosha\Import $shoshaImport
    ){
        parent::__construct($context);
        $this->_shoshaImport = $shoshaImport;
    }

    /**
     * Import rule from CSV action
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

            $importResult = $this->_shoshaImport->doImport('csv_file');
            $resultBlock->addSuccess(__('Import successfully done: %1 records', $importResult['success']));
            return $resultLayout;
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/customer/shosha/import');
        return $resultRedirect;
    }
}