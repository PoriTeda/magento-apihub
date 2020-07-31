<?php
namespace Riki\CedynaInvoice\Cron;

class ImportData
{
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Riki\CedynaInvoice\Helper\Validator
     */
    protected $helperValidator;
    /**
     * @var \Riki\CedynaInvoice\Model\InvoiceFactory
     */
    protected $invoiceFactory;

    /**
     * Importer constructor.
     * @param \Riki\CedynaInvoice\Helper\Data $helperData
     * @param \Riki\CedynaInvoice\Helper\Validator $validator
     * @param \Riki\CedynaInvoice\Model\InvoiceFactory $invoiceFactory
     */
    public function __construct(
        \Riki\CedynaInvoice\Helper\Data $helperData,
        \Riki\CedynaInvoice\Helper\Validator $validator,
        \Riki\CedynaInvoice\Model\InvoiceFactory $invoiceFactory
    ) {
        $this->helperData = $helperData;
        $this->helperValidator = $validator;
        $this->invoiceFactory = $invoiceFactory;
    }

    /**
     * main function
     */
    public function execute()
    {
        if ($this->helperData->isEnable()) {
            $this->doImportData();
        } else {
            $this->helperData->writeToLog(__('Module Cedyna Invoice has been disabled'));
        }
    }

    /**
     * Import data from sftp
     */
    private function doImportData()
    {
        $rows = $this->helperData->getSftpData();
        if (!empty($rows)) {
            foreach ($rows as $file => $fileRows) {
                $this->helperData->writeToLog(
                    __('Processing file : %1', $file),
                    false
                );
                foreach ($fileRows as $i => $txtRow) {
                    //validation
                    $index = $i+1;
                    if ($this->helperValidator->validateAllFields($index, $txtRow)) {
                        $dataRow = $this->helperData->convertData($txtRow);
                        $dataRow = $this->helperValidator->extractDataFromIncrementId($dataRow);
                        $invoiceObject = $this->invoiceFactory->create();
                        $invoiceObject->setData($dataRow);
                        try {
                            $invoiceObject->save();
                            $this->helperData->writeToLog(
                                __('Row[%1]: has been imported successfully.', $index),
                                false
                            );
                        } catch (\Exception $e) {
                            $this->helperData->writeToLog(__('Row[%1]: has not been imported .', $index));
                            $this->helperData->writeToLog($e->getMessage());
                            $this->helperData->writeToLog($e->getTraceAsString());
                        }
                    } else {
                        $this->helperData->writeToLog(
                            __('Row[%1]: has not been passed validation.', $index)
                        );
                    }
                }
            }
        }
    }
}
