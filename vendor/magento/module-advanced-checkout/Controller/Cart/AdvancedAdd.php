<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;

class AdvancedAdd extends \Magento\AdvancedCheckout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * Add to cart products, which SKU specified in request
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        // check empty data
        /** @var $helper \Magento\AdvancedCheckout\Helper\Data */
        $helper = $this->_objectManager->get(\Magento\AdvancedCheckout\Helper\Data::class);
        $items = $this->getRequest()->getParam('items');
        foreach ($items as $k => $item) {
            if (!isset($item['sku']) || (empty($item['sku']) && $item['sku'] !== '0')) {
                unset($items[$k]);
            }
        }
        if (empty($items) && !$helper->isSkuFileUploaded($this->getRequest())) {
            $this->messageManager->addError($helper->getSkuEmptyDataMessageText());
            return $resultRedirect->setPath('checkout/cart');
        }

        try {
            // perform data
            $cart = $this->_getFailedItemsCart()->prepareAddProductsBySku($items)->saveAffectedProducts();

            $this->messageManager->addMessages($cart->getMessages());

            if ($cart->hasErrorMessage()) {
                throw new LocalizedException(__($cart->getErrorMessage()));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addException($e, $e->getMessage());
        }
        $this->_eventManager->dispatch('collect_totals_failed_items');

        return $resultRedirect->setPath('checkout/cart');
    }
}
