<?php

namespace Riki\CsvOrderMultiple\Controller\Adminhtml\Csv;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_CsvOrderMultiple::csv_multiple_order');
        $resultPage->getConfig()->getTitle()->prepend(__('Download CSV Files'));
        return $resultPage;
    }

    /**
     * @return bool
     */

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CsvOrderMultiple::import_order_csv_download');
    }
}
