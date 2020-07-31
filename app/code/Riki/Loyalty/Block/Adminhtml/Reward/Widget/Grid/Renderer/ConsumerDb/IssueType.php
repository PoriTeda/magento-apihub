<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer\ConsumerDb;

class IssueType extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_apiHelper;

    /**
     * IssueType constructor.
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
        $this->_apiHelper = $helper;
    }

    /**
     * Point issue type
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $type = $row->getData('point_issue_type');
        return $this->_apiHelper->getIssueTypeLabel($type);
    }

}