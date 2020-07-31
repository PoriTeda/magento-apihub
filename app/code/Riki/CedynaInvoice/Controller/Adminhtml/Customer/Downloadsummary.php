<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Customer;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Downloadsummary
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Customer
 */
class Downloadsummary extends AbstractCustomer
{

    /**
     * Implement download action
     */
    public function execute()
    {
        $customerId = $this->_request->getParam('id');
        $targetMonth = $this->_request->getParam('target');
        $fileName = 'Cedyna_Invoice_'.$customerId.'_invoices_summary.csv';
        $invoiceFactory = $this->resourceInvoiceFactory->create();
        $invoices =  $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
        $invoicesContent = $this->helperData->buildInvoiceSummaryContent($invoices);
        return $this->fileFactory->create(
            $fileName,
            $invoicesContent,
            DirectoryList::VAR_DIR
        );
    }
}
