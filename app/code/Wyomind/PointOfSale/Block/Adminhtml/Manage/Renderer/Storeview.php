<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer;

class Storeview extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @var type
     */
    protected $_storeManager = null;

    /**
     * @var \Wyomind\PointOfSale\Helper\Data
     */
    protected $_helper = null;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\PointOfSale\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
        $this->_storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
    }

    public function render(\Magento\Framework\DataObject $row)
    {

        $return = "";

        $storeViewIds = explode(',', $row->getStoreId());

        $websites = $this->_storeManager->getWebsites();

        if ($storeViewIds[0] == 0 || count($storeViewIds) < 1) {
            $return = __("No Store View");
            return $return;
        }
        foreach ($websites as $website) {
            if ($this->in_array($storeViewIds, $website->getStoreIds())) {
                $return .= "<div style='float:left;  padding:0 5px'><b><u>" . $website->getName() . "</u></b><br>";
                $storegroups = $website->getGroupCollection();
                foreach ($storegroups as $storegroup) {
                    if ($this->in_array($storeViewIds, $storegroup->getStoreIds())) {
                        $return .= "<b style='padding-left:5px;'>" . $storegroup->getName() . "</b><br>";
                        $storeviews = $storegroup->getStoreCollection();
                        foreach ($storeviews as $storeview) {
                            if (in_array($storeview->getId(), $storeViewIds)) {
                                $return .= "<span style='padding-left:10px;'>" . $storeview->getName() . "</span><br>";
                            }
                        }
                    }
                }
                $return .= "<div>";
            }
        }
        return $return;
    }

    public function in_array(
        $arrayOne,
        $arrayTwo
    ) {
        foreach ($arrayOne as $value) {
            if (in_array($value, $arrayTwo)) {
                return true;
            }
        }
        return false;
    }
}
