<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Block;

class Items extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Amasty\Promo\Helper\Data
     */
    protected $promoHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Image\View
     */
    protected $_productImageView;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $_urlHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Amasty\Promo\Helper\Data $promoHelper,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Framework\Url\Helper\Data $urlHelper
    ) {
        parent::__construct($context, $data);

        $this->promoHelper = $promoHelper;
        $this->_helperImage = $helperImage;
        $this->_urlHelper = $urlHelper;
    }

    protected function _toHtml()
    {
        if ($this->getItems())
            return parent::_toHtml();
        else
            return false;
    }

    public function getItems()
    {
        return $this->promoHelper->getNewItems();
    }

    public function getImageHelper()
    {
        return $this->_helperImage;
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('amasty_promo/cart/add');
    }

    public function getCurrentBase64Url()
    {
        return $this->_urlHelper->getCurrentBase64Url();
    }
}
