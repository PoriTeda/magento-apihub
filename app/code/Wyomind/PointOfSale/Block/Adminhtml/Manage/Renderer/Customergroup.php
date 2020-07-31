<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer;

class Customergroup extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_customerGroupModel = null;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Customer\Model\Group $customerGroupModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_customerGroupModel = $customerGroupModel;
    }

    public function render(\Magento\Framework\DataObject $row)
    {

        $_customerGroup = [];
        $output = null;
        $allGroups = $this->_customerGroupModel->getCollection()->toOptionHash();

        foreach ($allGroups as $key => $allGroup) {
            $_customerGroup[$key] = $allGroup;
        }
        $selection = explode(',', $row->getCustomerGroup());

        if (in_array('-1', $selection) || count($selection) < 1) {
            return __("No Customer Group");
        } else {
            foreach ($selection as $v) {
                if (isset($_customerGroup[$v])) {
                    $output.=$_customerGroup[$v] . "<br>";
                }
            }
        }
        return $output;
    }
}
