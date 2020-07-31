<?php
namespace Nestle\Catalog\Plugin;

use Magento\Framework\Exception\LocalizedException;

class Product
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
     * Logging for gps_price, gps_price_ec changes
     *
     * @param  \Magento\Catalog\Model\Product $subject
     * @throws \Zend_Serializer_Exception
     */
    public function beforeBeforeSave(\Magento\Catalog\Model\Product $subject)
    {
        // Log trace NED-1666
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-1666.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        if ($subject->dataHasChangedFor('gps_price') && empty($subject->getData('gps_price'))) {
            if ($subject->getData('gps_price') === null) {
                $subject->setData('gps_price', $subject->getOrigData('gps_price'));
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product ID #%1 attribute gps_price was been changed from %2 to null - Store ID %3 - Reverted by plugin',
                    $subject->getId(),
                    $subject->getOrigData('gps_price'),
                    $subject->getStoreId()
                )));
            } else {
                // Only log, not update.
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product ID #%1 attribute gps_price was been changed from %2 to %3 - Store ID %4',
                    $subject->getId(),
                    $subject->getOrigData('gps_price'),
                    $subject->getData('gps_price'),
                    $subject->getStoreId()
                )));
            }
        }

        if ($subject->dataHasChangedFor('gps_price_ec') && empty($subject->getData('gps_price_ec'))) {
            if ($subject->getData('gps_price_ec') === null) {
                $subject->setData('gps_price_ec', $subject->getOrigData('gps_price_ec'));
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product ID #%1 attribute gps_price_ec has been changed from %2 to null - Store ID %3 - Reverted by plugin',
                    $subject->getId(),
                    $subject->getOrigData('gps_price_ec'),
                    $subject->getStoreId()
                )));
            } else {
                // Only log, not update.
                $this->productGpsPriceLogger->debug(new LocalizedException(__(
                    'Product ID #%1 attribute gps_price_ec has been changed from %2 to %3 - Store ID %4',
                    $subject->getId(),
                    $subject->getOrigData('gps_price_ec'),
                    $subject->getData('gps_price_ec'),
                    $subject->getStoreId()
                )));
            }
        }
    }
}
