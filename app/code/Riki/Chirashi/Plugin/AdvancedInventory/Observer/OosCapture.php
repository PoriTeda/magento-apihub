<?php
namespace Riki\Chirashi\Plugin\AdvancedInventory\Observer;

class OosCapture
{
    /**
     * Not generate OOS data for Chirashi product
     *
     * @param \Riki\AdvancedInventory\Observer\OosCapture $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return array
     */
    public function beforeCanCapture(
        \Riki\AdvancedInventory\Observer\OosCapture $subject,
        \Magento\Framework\Event\Observer $observer
    )
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        if ($product instanceof \Magento\Catalog\Model\Product) {
            if ($product->getChirashi()) {
                $observer->getEvent()->setData('product', null);
            }
        }

        return [$observer];
    }
}
