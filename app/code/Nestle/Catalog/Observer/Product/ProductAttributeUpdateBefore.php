<?php
namespace Nestle\Catalog\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class ProductAttributeUpdateBefore implements ObserverInterface
{
    /**
     * @var \Nestle\Catalog\Logger\ProductGpsPriceLogger
     */
    protected $productGpsPriceLogger;

    /**
     * SendCutOffEmail constructor.
     *
     * @param \Nestle\Catalog\Logger\ProductGpsPriceLogger $productGpsPriceLogger
     */
    public function __construct(
        \Nestle\Catalog\Logger\ProductGpsPriceLogger $productGpsPriceLogger
    ) {
        $this->productGpsPriceLogger = $productGpsPriceLogger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productIds = $observer->getProductIds();

        $attributesData = $observer->getAttributesData();
        if (!empty($attributesData)) {
            if (isset($attributesData['gps_price']) && empty($attributesData['gps_price'])) {
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product IDs #%1 attribute gps_price has been mass updated to null/empty',
                    json_encode($productIds)
                )));
            }

            if (isset($attributesData['gps_price_ec']) && empty($attributesData['gps_price_ec'])) {
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product IDs #%1 attribute gps_price_ec has been mass updated to null/empty',
                    json_encode($productIds)
                )));
            }
        }
    }
}
