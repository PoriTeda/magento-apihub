<?php

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Import;

use Magento\ImportExport\Controller\Adminhtml\ImportResult as ImportResultController;
use Magento\ImportExport\Block\Adminhtml\Import\Frame\Result as ImportResultBlock;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\ImportExport\Model\Import\Adapter as ImportAdapter;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class Validate extends ImportResultController
{

    protected $importHandler;

    protected $fileSystem;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\ImportExport\Model\Report\ReportProcessorInterface $reportProcessor,
        \Magento\ImportExport\Model\History $historyModel,
        \Magento\ImportExport\Helper\Report $reportHelper,
        \Riki\SubscriptionCourse\Model\ImportHandler $importHandler,
        \Magento\Framework\Filesystem $fileSystem
    ) {
        parent::__construct($context, $reportProcessor, $historyModel, $reportHelper);
        $this->importHandler = $importHandler;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Validate uploaded files action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /**
         * @var \Magento\Framework\View\Result\Layout $resultLayout
         */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        /**
         * @var $resultBlock ImportResultBlock
         */
        $resultBlock = $resultLayout->getLayout()->getBlock('import.frame.result');
        if ($data) {
            // common actions
            $resultBlock->addAction(
                'show',
                'import_validation_container'
            );
            $data['entity'] = 'riki_subscription_course';
            $data[\Magento\ImportExport\Model\Import::FIELD_NAME_VALIDATION_STRATEGY] = \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS;
            $data[\Magento\ImportExport\Model\Import::FIELD_NAME_ALLOWED_ERROR_COUNT] = \Riki\SubscriptionCourse\Model\ImportHandler::ALLOWED_ERRORS_COUNT;

            /**
             * @var \Riki\SubscriptionCourse\Model\ImportHandler $import
             */
            $import = $this->importHandler->setData($data);
            // Validate file extension
            $error = $this->validateFileExtension();
            if ($error) {
                $resultBlock->addError($error);
                return $resultLayout;
            }

            try {
                $source = ImportAdapter::findAdapterFor(
                    $import->uploadSource(),
                    $this->fileSystem->getDirectoryWrite(DirectoryList::ROOT)
                );

                $validationResult = $import->validateSource($source);

                if (!$import->getProcessedRowsCount()) {
                    if (!$import->getErrorAggregator()->getErrorsCount()) {
                        $resultBlock->addError(__('This file is empty. Please try another one.'));
                    } else {
                        foreach ($import->getErrorAggregator()->getAllErrors() as $error) {
                            $resultBlock->addError($error->getErrorMessage(), false);
                        }
                    }
                } else {
                    $errorAggregator = $import->getErrorAggregator();
                    if (!$validationResult) {
                        $resultBlock->addError(
                            __('Data validation is failed. Please fix errors and re-upload the file..')
                        );
                        $this->addErrorMessages($resultBlock, $errorAggregator);
                    } else {
                        if ($import->isImportAllowed()) {
                            $resultBlock->addSuccess(
                                __('File is valid! To start import process press "Import" button'),
                                true
                            );
                        } else {
                            $resultBlock->addError(
                                __('The file is valid, but we can\'t import it for some reason.'),
                                false
                            );
                        }
                    }
                    $resultBlock->addNotice(
                        __(
                            'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                            $import->getProcessedRowsCount(),
                            $import->getProcessedEntitiesCount(),
                            $errorAggregator->getInvalidRowsCount(),
                            $errorAggregator->getErrorsCount()
                        )
                    );
                }
                return $resultLayout;
            } catch (\Exception $e) {
                $resultBlock->addError(__($e->getMessage()));
                return $resultLayout;
            }
        } elseif ($this->getRequest()->isPost() && empty($this->getRequest()->getFiles())) {
            $resultBlock->addError(__('The file was not uploaded.'));
            return $resultLayout;
        }
        $this->messageManager->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
        /**
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/upload');
        return $resultRedirect;
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $resultBlock
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return $this
     */
    protected function addErrorMessages(
        \Magento\Framework\View\Element\AbstractBlock $resultBlock,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        if ($errorAggregator->getErrorsCount()) {
            $message = "";
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                ++$counter;
                $message .= $counter . "." . $error . '<br>';
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $error) {
                    $message .= $error->getErrorMessage()
                        . ' <a href="#" onclick="$(this).next().show();$(this).hide();return false;">'
                        . __('Show more') . '</a><div style="display:none;">' . __('Additional data') . ': '
                        . $error->getErrorDescription() . '</div>';
                }
            }
            try {
                $resultBlock->addNotice(
                    '<strong>' . __('Following Error(s) has been occurred during importing process:') . '</strong><br>'
                    . '<div class="import-error-list">' . $message . '</div></div>'
                );
            } catch (\Exception $e) {
                foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                    $resultBlock->addError($errorMessage);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function createDownloadUrlImportHistoryFile($fileName)
    {
        return $this->getUrl('adminhtml/history/download', ['filename' => $fileName]);
    }

    /**
     * Import start action URL.
     *
     * @return string
     */
    protected function getImportStartUrl()
    {
        return $this->getUrl('adminhtml/*/start');
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    protected function validateFileExtension()
    {
        $error = '';

        if ($this->getRequest()->getFiles() && $this->getRequest()->getFiles()->get('import_file')['name']) {
            $extension = pathinfo(
                $this->getRequest()->getFiles()->get('import_file')['name'],
                PATHINFO_EXTENSION
            );
            if ($extension && $extension != 'csv') {
                $error = __('%1 file extension is not supported', $extension);
            }
        }

        return $error;
    }

    /**
     * @return bool
     */

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::import_csv');
    }
}
