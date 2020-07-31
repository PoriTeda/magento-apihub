<?php

namespace Riki\User\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Validate extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;
    /**
     * @var \Riki\User\Model\Password\Import
     */
    protected $_import;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Riki\User\Model\Password\Import $import
    ) {
        $this->_uploaderFactory = $uploaderFactory;
        $this->_import = $import;
        parent::__construct($context);
    }

    /**
     * Validate uploaded files action.
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
            $resultBlock = $resultLayout->getLayout()->getBlock('user.import.frame.result');
            $resultBlock->addAction(
                'show',
                'import_validation_container'
            );

            $messages = $this->_import->validateSource('csv_import_password');
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
        $resultRedirect->setPath('adminhtml/*/index');

        return $resultRedirect;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_User::import_password');
    }
}
