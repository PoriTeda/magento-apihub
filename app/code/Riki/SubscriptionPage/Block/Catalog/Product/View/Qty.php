<?php


namespace Riki\SubscriptionPage\Block\Catalog\Product\View;


use Riki\SubscriptionPage\Block\SubscriptionView;

class Qty extends \Magento\Framework\View\Element\Template
{
    private $hanpukaiCart = [];

    public function getProductQtyJsData()
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        return json_encode([
            "productId" => $this->getProduct()->getId(),
            "categoryId" => $this->getCategoryId(),
            "isHanpukai" => $this->getIsHanpukai(),
            "minQty" => $this->getViewModel()->getMinimalQty($this->getProduct()),
            "maxQty" => $this->getViewModel()->getMaximumQty($this->getProduct()),
            "isDisable" => !$this->getIsSalable() || ($this->isSpotOrder() && !$this->isAllowSpotOrder())
        ]);
    }

    public function isHanpukai()
    {
        return (bool)$this->getData("is_hanpukai");
    }

    /**
     * Add product id
     *
     * @param $data
     *
     * @return $this
     */
    public function addToHanpukaiCart($data)
    {
        SubscriptionView::$hanpukaiCart[$data['product_id']] = $data;
        return $this;
    }

    public function isSpotOrder()
    {
        return $this->getData("is_spot_order") ? $this->getData("is_sport_order") : false;
    }

    public function isAllowSpotOrder()
    {
        return !($this->getProduct()->getCustomAttribute('allow_spot_order')
            && $this->getProduct()->getCustomAttribute('allow_spot_order')->getValue() != '1');

    }
}