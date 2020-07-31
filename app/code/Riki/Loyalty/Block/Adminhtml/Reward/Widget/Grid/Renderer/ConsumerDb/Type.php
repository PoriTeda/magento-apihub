<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer\ConsumerDb;

class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_apiHelper;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\Loyalty\Helper\Api $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_apiHelper = $helper;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $type = $row->getData('point_type');
        return $this->_apiHelper->getTypeLabel($type);
    }

}