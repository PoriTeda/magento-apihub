<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_helper;

    /**
     * Status constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Riki\Loyalty\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\Loyalty\Helper\Data $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    /**
     * Point issue status label
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $status = $row->getData('status');
        return $this->_helper->getPointStatusLabel($status);
    }

}