<?php

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Stocks\Renderer;

class Websites extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_collection = null;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\AdvancedInventory\Model\ResourceModel\WebsitesLinks\CollectionFactory $collection,
        array $data = []
    ) {
        $this->_collection = $collection;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $collection = $this->_collection->create();
        $collection->getWebsitesIds($row->getId());
        $websites = [];
        foreach ($collection as $item) {
            $websites[] = $item->getName();
        }
        
        return implode(", ", $websites);
    }
}
