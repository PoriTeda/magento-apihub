<?php

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Journal\Renderer;

class Reference extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $_helperCore;
    protected $_posModel;
    protected $_productModel;
    protected $_orderModel;
    protected $_storeManager;
    protected $_helperData;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\PointOfSale\Model\PointOfSale $posModel,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Store\Model\StoreManager $_storeManager,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        array $data = []
    ) {


        $this->_helperCore = $helperCore;
        $this->_posModel = $posModel;
        $this->_productModel = $productModel;
        $this->_orderModel = $orderModel;
        $this->_storeManager = $_storeManager;
        $this->_helperData = $helperData;

        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\DataObject $row)
    {

        foreach (explode(",", $row->getReference()) as $ref) {
            $ref = explode("#", $ref);

            switch ($ref[0]) {
                case "S":
                    $store = $this->_storeManager->getStore($ref[1]);
                    $group = $this->_storeManager->getGroup($store->getGroupId());
                    $website = $this->_storeManager->getWebsite($store->getWebsiteId());
                    $title[] = $website->getName() . " > " . $group->getName() . " > " . $store->getName();
                    break;
                case "O":
                    $data = $this->_orderModel->load($ref[1])->getIncrementId();
                    $title[] = "Order #" . $data;
                    break;
                case "P":
                    $data = $this->_productModel->load($ref[1])->getSku();
                    $title[] = "Sku : " . $data;
                    break;
                case "W":
                    $data = $this->_posModel->load($ref[1])->getName();
                    $title[] = "WH/POS : " . $data;
                    break;
            }
        };
        return "<span title=\"" . implode("\n", $title) . "\">" . ($row->getReference()) . "</span>";
    }
}
