<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Observer;

use Magento\Framework\Event\ObserverInterface;

class PrepareRuleSave implements ObserverInterface
{
    /**
     * Adminhtml js
     *
     * @var \Magento\Backend\Helper\Js
     */
    protected $_adminhtmlJs = null;

    /**
     * @param \Magento\Backend\Helper\Js $adminhtmlJs
     */
    public function __construct(
        \Magento\Backend\Helper\Js $adminhtmlJs
    ) {
        $this->_adminhtmlJs = $adminhtmlJs;
    }

    /**
     * Prepare sales rule post data to save
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Banner\Observer\PrepareRuleSave
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $relatedBanners = $request->getPost('related_banners');
        $banners = $relatedBanners;
        if (!is_array($relatedBanners)) {
            $banners = $this->_adminhtmlJs->decodeGridSerializedInput($relatedBanners);
        }
        $request->setPostValue('related_banners', $banners);

        return $this;
    }
}
