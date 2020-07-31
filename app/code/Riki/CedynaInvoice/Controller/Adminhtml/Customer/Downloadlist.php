<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Customer;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Downloadlist
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Customer
 */
class Downloadlist extends AbstractCustomer
{

    /**
     * Implement download action
     */
    public function execute()
    {
        $customerId = $this->_request->getParam('id');
        $fileName = 'Cedyna_Invoice_'.$customerId.'_invoices.csv';
        $targetMonth = $this->_request->getParam('target');
        $invoiceFactory = $this->resourceInvoiceFactory->create();
        $invoices =  $invoiceFactory->getMonthlyInvoicesByCustomer($customerId, $targetMonth);
        $csvHeader = $this->helperData->encodeByLocale([
            __('INVOICE DETAIL SHIPMENT INCREMENT ID'),
            __('INVOICE DETAIL ORDER CREATED'),
            __('SHIPPED OUT DATE / RETURNED DATE'),
            __('INVOICE DETAIL PRODUCT LINE NAME'),
            __('INVOICE DETAIL UNIT PRICE'),
            __('INVOICE DETAIL QTY'),
            __('INVOICE DETAIL ROW TOTAL'),
            __('INVOICE DETAIL SHIPPING ADDRESS')
        ]);
        $invoicesRows[] = implode(',', $csvHeader);
        $invoicesContent = '';
        if ($invoices) {
            foreach ($invoices as $invoice) {
                if ($invoice['data_type'] ==
                    \Riki\CedynaInvoice\Model\Source\Config\DataType::DATA_TYPE_OPTION_SALES) {
                    $rowTotal = (int)$invoice['row_total'];
                } else {
                    $rowTotal = -1*(int)$invoice['row_total'];
                }
                $shipmentDate = $this->helperData->getShipmentDate($invoice);
                if ($shipmentDate) {
                    $shipmentDate = $this->helperData->formatDate($shipmentDate);
                }
                $tempRow = $this->helperData->encodeByLocale([
                    $invoice['increment_id'],
                    $this->helperData->formatDate($invoice['order_created_date']),
                    $shipmentDate,
                    $invoice['product_line_name'],
                    (int)$invoice['unit_price'],
                    (int)$invoice['qty'],
                    (int)$rowTotal,
                    $invoice['riki_nickname']
                ]);
                $invoicesRows[] = implode(',', $tempRow);
            }
            $invoicesContent = implode("\n", $invoicesRows);
        }
        return $this->fileFactory->create(
            $fileName,
            $invoicesContent,
            DirectoryList::VAR_DIR
        );
    }
}
