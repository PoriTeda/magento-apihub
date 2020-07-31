<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer\ConsumerDb;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
        $status = $row->getData('point_issue_status');
        return $this->_apiHelper->getPointStatusLabel($status);
    }

}