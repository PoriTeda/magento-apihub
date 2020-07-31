<?php

namespace Riki\SubscriptionPage\Block\Catalog\Product\View;

use \Magento\Catalog\Block\Product\View\Gallery as Gallery;
use Magento\Framework\App\ObjectManager;

class ProductGallery extends Gallery {

    protected $productId;

    public function getProduct(){
        $productFactory = ObjectManager::getInstance()->get('\Magento\Catalog\Model\ProductFactory');
        return $productFactory->create()->load($this->productId);
    }

    public function setProductId($productId){
        $this->productId = $productId;
    }

    public function getMagnifier()
    {
        return $this->jsonEncoder->encode($this->getVar('magnifier', "Magento_Catalog"));
    }

    public function getBreakpoints()
    {
        return $this->jsonEncoder->encode($this->getVar('breakpoints', "Magento_Catalog"));
    }
}