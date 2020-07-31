<?php
namespace Riki\ProductActive\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Bundle\Model\Product\Type as ProductType;

class ProductValidation implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $launchFrom =  strtotime($product->getLaunchFrom());
        $launchTo =   strtotime($product->getLaunchTo());

        if(($product->getLaunchTo() && $launchFrom && $launchFrom && ($launchFrom > $launchTo)))
        {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Launch Date To : %s should always be greater than or equal Lauch Date From: %s',date('m/d/Y',$launchTo),
                    date('m/d/Y',$launchFrom))));
        }

        if ($this->notSaveMultiProductInOneOption($product) == false) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(sprintf('Not allow to create bundle product with more than one product per one option')));
        }
    }

    public function notSaveMultiProductInOneOption($product)
    {
        /* @var \Magento\Catalog\Model\Product $product */
        if ($product->getTypeId() == ProductType::TYPE_CODE) {
            $bundleSelectionsData = $product->getData('bundle_selections_data');
            if (is_array($bundleSelectionsData)) {
                foreach ($bundleSelectionsData as $selections) {
                    $arrProduct = array();
                    foreach ($selections as $productChild) {
                        if ($productChild['delete'] != '1') {
                            $arrProduct[] = $productChild['product_id'];
                        }
                    }
                    if (count($arrProduct) > 1) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}
