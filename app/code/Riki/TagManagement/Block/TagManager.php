<?php

namespace Riki\TagManagement\Block;

/**
 * Class Form
 * @package Riki
 */
class TagManager extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Riki\TagManagement\Helper\Helper
     */
    protected $helper;

    /**
     * TagManager constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\TagManagement\Helper\Helper $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\TagManagement\Helper\Helper $helper,
        array $data = []
    )
    {
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
        if (false != $this->getTemplate()) {
            return parent::_toHtml();
        }
        $html = '';
        $html .= $this->helper->getConfigYahoo();
        return $html;
    }
}