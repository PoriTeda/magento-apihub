<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Framework\App\Filesystem\DirectoryList;

class PrintAction extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Generate PDF form of RMA
     *
     * @return void|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $rmaId = (int)$this->getRequest()->getParam('rma_id');
        if ($rmaId) {
            /** @var $rmaModel \Magento\Rma\Model\Rma */
            $rmaModel = $this->_objectManager->create(\Magento\Rma\Model\Rma::class)->load($rmaId);
            if ($rmaModel) {
                /** @var $dateModel \Magento\Framework\Stdlib\DateTime\DateTime */
                $dateModel = $this->_objectManager->get(\Magento\Framework\Stdlib\DateTime\DateTime::class);
                /** @var $pdfModel \Magento\Rma\Model\Pdf\Rma */
                $pdfModel = $this->_objectManager->create(\Magento\Rma\Model\Pdf\Rma::class);
                $pdf = $pdfModel->getPdf([$rmaModel]);
                return $this->_fileFactory->create(
                    'rma' . $dateModel->date('Y-m-d_H-i-s') . '.pdf',
                    $pdf->render(),
                    DirectoryList::MEDIA,
                    'application/pdf'
                );
            }
        } else {
            $this->_forward('noroute');
        }
    }
}
