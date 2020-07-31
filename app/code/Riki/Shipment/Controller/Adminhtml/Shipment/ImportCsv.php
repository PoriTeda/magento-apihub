<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Shipment\Controller\Adminhtml\Shipment;

use Magento\Framework\Controller\ResultFactory;

class ImportCsv extends \Magento\Backend\App\Action
{
    /**
     * Import and export Page
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Riki\Shipment\Block\Adminhtml\Shipment\ImportCsv')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Upload CSV shipments'));
        return $resultPage;
    }
    /**
     * Is Allow.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('Riki_Shipment::rikiship_importcsv'))
        {
            return true;
        }
        return false;
    }
}
