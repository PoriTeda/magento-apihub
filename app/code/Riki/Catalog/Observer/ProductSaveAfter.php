<?php

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Catalog\Api\Data\SapProductInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi
     */
    protected $loggerImportSap;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * ProductSaveAfter constructor.
     * @param \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi $loggerImportSap,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->loggerImportSap = $loggerImportSap;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $isEnableSapLog = $this->scopeConfig->getValue(
            \Riki\AdvancedInventory\Logger\LoggerImportProductSapApi::CONFIG_PATH_LOGGER_IMPORT_PRODUCT_SAP_API_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        if ($isEnableSapLog) {
            $product = $observer->getProduct();
            if ($product->getId()) {
                $sku = $product->getSku();
                $fieldsCheckDiff = [
                    SapProductInterface::UNIT_SAP,
                    SapProductInterface::GPS_PRICE,
                    SapProductInterface::SALES_ORGANIZATION,
                    SapProductInterface::FUTURE_GPS_PRICE,
                    SapProductInterface::FUTURE_GPS_PRICE_EC
                ];

                foreach ($fieldsCheckDiff as $field) {
                    if ($product->dataHasChangedFor($field)) {
                        $this->loggerImportSap->info(sprintf('Data field %s of product SKU #%s changed from: %s to %s', $field, $sku, $product->getOrigData($field), $product->getData($field)));
                    }
                }
            } else {
                $this->loggerImportSap->info(__('This product doesn\'t exist.'));
            }
        }
    }
}
