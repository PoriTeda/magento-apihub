<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Theme\Block\Html\Header;

/**
 * Logo page header block
 */
class Logo extends \Magento\Theme\Block\Html\Header\Logo
{
    /**
     * @var \Riki\Checkout\Block\Checkout\Onepage
     */
    protected $blockOnePage;
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper
     * \Riki\Checkout\Block\Checkout\Onepage\Success $onepage
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageHelper,
        \Riki\Checkout\Block\Checkout\Onepage\Success $onepage,
        array $data = []
    ) {
        $this->blockOnePage = $onepage;
        parent::__construct($context, $fileStorageHelper, $data);
    }

    public function getLinkBlockCart(){
        return $this->blockOnePage->getUrlTopPage();
    }
}
