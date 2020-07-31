<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    protected $userId;
    protected $userName;
    protected $allowedExtensions = ['csv'];
    protected $fileId = 'csv_file';
    protected $paymentMethod;


    /**
     * Save constructor.
     * @param Action\Context $context
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->directoryList = $directoryList;

        $this->userId = $authSession->getUser()->getId();
        $this->userName = $authSession->getUser()->getUserName();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addError(__('Import data is invalid.'));
        }

        /* @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_LAYOUT);

        /** @var \Riki\ArReconciliation\Block\Adminhtml\Import\Frame\Save $resultBlock */
        $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.save');

        $dataPost = $this->getRequest()->getParams();

        if ($dataPost) {
            try {
                $this->paymentMethod = $dataPost['payment_type'];
                $this->saveImportFile();
                $this->messageManager->addSuccess(__('Import csv file success!'));
                /*import success -> redirect to grid view*/
                $resultBlock->setReponseType(1);
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        return $resultLayout;
    }

    /**
     * get import file content
     * @throws LocalizedException
     */
    public function saveImportFile()
    {
        $destinationPath = $this->getDestinationPath();
        $newFileName = $this->userName . '-' . $this->userId . '-' . time() . '.csv';
        $uploader = $this->uploaderFactory->create(['fileId' => $this->fileId])
            ->setAllowCreateFolders(true)
            ->setAllowedExtensions($this->allowedExtensions)
            ->setAllowRenameFiles(true)
            ->addValidateCallback('validate', $this, 'validateFile');
        if (!$uploader->save($destinationPath, $newFileName)) {
            throw new LocalizedException(
                __('File cannot be saved to path: $1', $destinationPath)
            );
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDestinationPath()
    {
        $varDirectory = $this->directoryList->getPath(
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR
        );

        $path = $varDirectory . DIRECTORY_SEPARATOR . Validate::AR_PAYMENT_CSV_FOLDER . DIRECTORY_SEPARATOR
            . $this->paymentMethod;

        $fileObject = new File();

        if (!$fileObject->isDirectory($path)) {
            $fileObject->createDirectory($path, 0777);
        }

        return $path;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ArReconciliation::import_payment_csv_file');
    }
}
