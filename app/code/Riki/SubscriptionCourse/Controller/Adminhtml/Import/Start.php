<?php

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Import;

use Magento\ImportExport\Controller\Adminhtml\ImportResult as ImportResultController;
use Magento\Framework\Controller\ResultFactory;
use Magento\ImportExport\Model\Import;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\SubscriptionCourse\Model\ImportHandler\SubscriptionCourse;

class Start extends ImportResultController
{
    /**
     * @var \Riki\SubscriptionCourse\Model\ImportHandler
     */
    protected $importModel;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;

    /**
     * Start constructor.
     *
     * @param  \Magento\Backend\App\Action\Context $context
     * @param  \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor
     * @param  \Magento\ImportExport\Model\History $historyModel
     * @param  \Magento\ImportExport\Helper\Report $reportHelper
     * @param  \Riki\SubscriptionCourse\Model\ImportHandler $importModel
     * @param  \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param  \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor,
        \Magento\ImportExport\Model\History $historyModel,
        \Magento\ImportExport\Helper\Report $reportHelper,
        \Riki\SubscriptionCourse\Model\ImportHandler $importModel,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context, $reportProcessor, $historyModel, $reportHelper);
        $this->importModel = $importModel;
        $this->uploaderFactory = $uploaderFactory;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Layout
     * @throws \Exception
     */
    public function execute()
    {
        // Saving uploaded csv file
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            /**
             * @var \Magento\Framework\View\Result\Layout $resultLayout
             */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            /**
             * @var $resultBlock \Magento\ImportExport\Block\Adminhtml\Import\Frame\Result
             */
            $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');
            $resultBlock
                ->addAction('show', 'import_validation_container')
                ->addAction('innerHTML', 'import_validation_container_header', __('Status'))
                ->addAction('hide', ['edit_form', 'upload_button', 'messages']);

            // return if there is an existing file
            $filePath = $this->varDirectory->getAbsolutePath(SubscriptionCourse::FOLDER_NAME)
                . DIRECTORY_SEPARATOR . SubscriptionCourse::FILE_NAME;
            if (file_exists($filePath)) {
                $resultBlock->addNotice(
                    __('There is an existing file waiting for executing by cron. Please try later!')
                );
                return $resultLayout;
            }
            // save file to specified folder
            /**
             * @var $uploader \Magento\MediaStorage\Model\File\Uploader
             */
            $uploader = $this->uploaderFactory->create(['fileId' => Import::FIELD_NAME_SOURCE_FILE]);
            $uploader->skipDbProcessing(true);
            $result = $uploader->save(
                $this->varDirectory->getAbsolutePath(SubscriptionCourse::FOLDER_NAME),
                SubscriptionCourse::FILE_NAME
            );
            if ($result) {
                $resultBlock->addSuccess(__('Import successfully done'));
            } else {
                $resultBlock->addError(__('Can not save uploaded file.'));
            }

            return $resultLayout;
        }
        /**
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/subscription/index');
        return $resultRedirect;
    }

    /**
     * @return bool
     */

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::import_csv');
    }
}
