<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer;

class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_helper;

    /**
     * Type constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Riki\Loyalty\Helper\Api $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\Loyalty\Helper\Api $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     *  Point issue type
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $type = $row->getData('point_type');
        return $this->_helper->getIssueTypeLabel($type);
    }

}