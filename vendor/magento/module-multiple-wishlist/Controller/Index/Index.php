<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Wishlist\Controller\Index\Index
{
    /**
     * Display customer wishlist
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /* @var $helper \Magento\MultipleWishlist\Helper\Data */
        $helper = $this->_objectManager->get(\Magento\MultipleWishlist\Helper\Data::class);
        if (!$helper->isMultipleEnabled()) {
            $wishlistId = $this->getRequest()->getParam('wishlist_id');
            if ($wishlistId && $wishlistId != $helper->getDefaultWishlist()->getId()) {
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($helper->getListUrl());
                return $resultRedirect;
            }
        }
        return parent::execute();
    }
}
