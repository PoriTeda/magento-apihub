<?php

namespace Riki\Rma\Controller\Adminhtml\Returns;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCsv extends \Magento\Reports\Controller\Adminhtml\Report\Sales
{
    /**
     * Export return report grid to CSV format
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'return.csv';
        $grid = $this->_view->getLayout()->createBlock('Magento\Rma\Block\Adminhtml\Rma\Grid')->setSaveParametersInSession(true);
        return $this->_fileFactory->create($fileName, $grid->getCsvFile(), DirectoryList::VAR_DIR);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Rma::rma_return');
    }
}
