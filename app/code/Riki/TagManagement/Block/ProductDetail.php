<?php

namespace Riki\TagManagement\Block;

class ProductDetail extends \Magento\Framework\View\Element\Template
{
    const CODE_SUBSCIPSION = 'RT000001S';
    /**
     * @var \Riki\TagManagement\Helper\Helper
     */
    protected $helper;
    /**
     * @var
     */
    protected $registry;
    /**
     * ProductDetail constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\TagManagement\Helper\Helper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\TagManagement\Helper\Helper $helper,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $this->registry = $registry;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = '';
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }
        /**
         * @var \Magento\Catalog\Model\Product $currenProduct
         */
        return $html;
    }
}