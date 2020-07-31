<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Adminhtml\Index;

use Magento\Framework\Exception\LocalizedException;

class UploadSkuCsv extends \Magento\AdvancedCheckout\Controller\Adminhtml\Index
{
    /**
     * Upload and parse CSV file with SKUs and quantity
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        try {
            $this->_initData();
        } catch (LocalizedException $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->_redirect('customer/index');
            $this->_redirectFlag = true;
        }
        if ($this->_redirectFlag) {
            return;
        }

        /** @var $helper \Magento\AdvancedCheckout\Helper\Data */
        $helper = $this->_objectManager->get(\Magento\AdvancedCheckout\Helper\Data::class);
        $rows = $helper->isSkuFileUploaded($this->getRequest()) ? $helper->processSkuFileUploading() : [];

        $items = $this->getRequest()->getPost('add_by_sku');
        if (!is_array($items)) {
            $items = [];
        }
        $result = [];
        foreach ($items as $sku => $qty) {
            $result[] = ['sku' => $sku, 'qty' => $qty['qty']];
        }
        foreach ($rows as $row) {
            $result[] = $row;
        }

        if (!empty($result)) {
            $cart = $this->getCartModel();
            $cart->prepareAddProductsBySku($result);
            $cart->saveAffectedProducts($this->getCartModel(), true);
        }

        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
    }
}
