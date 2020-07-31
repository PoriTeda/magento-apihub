<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer;

class Store extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_posHelper = null;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\PointOfSale\Helper\Data $posHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_posHelper = $posHelper;
    }

    /**
     * Renders grid column
     * @param   \Magento\Framework\Object $row
     * @return  string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $content = "";
        $content.= "<b>" . $row->getName() . ' [' . $row->getStoreCode() . ']</b><br>';
        $content.= $this->_posHelper->getStoreDescription($row);
        return $content;
    }
}
