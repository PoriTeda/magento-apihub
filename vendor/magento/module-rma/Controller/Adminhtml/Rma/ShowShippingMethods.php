<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class ShowShippingMethods extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Shows available shipping methods
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $response = false;

        try {
            $model = $this->_initModel();
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('This is the wrong RMA ID.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We cannot display the available shipping methods.')];
        }

        $this->_view->loadLayout();
        $response = $this->_view->getLayout()->getBlock('magento_rma_shipping_available')->toHtml();

        if (is_array($response)) {
            $this->getResponse()->representJson(
                $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response)
            );
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
