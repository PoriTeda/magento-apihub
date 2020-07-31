<?php

namespace Riki\AdvancedInventory\Model\OutOfStock;

use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Tax\Api\Data\TaxClassKeyInterface;

class ShippingCalculator
{
    /**
     * @var \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory
     */
    protected $taxClassKeyFactory;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory
     */
    protected $quoteDetailsItemFactory;

    /**
     * @var \Magento\Tax\Model\Config $taxConfig
     */
    protected $taxConfig;

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory
     */
    protected $quoteDetailsFactory;

    /**
     * @var \Magento\Tax\Api\TaxCalculationInterface
     */
    protected $taxCalculationService;

    /**
     * ShippingCalculator constructor.
     * @param \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory
     * @param \Magento\Tax\Api\TaxCalculationInterface $taxCalculation
     * @param \Magento\Tax\Model\Config $taxConfig
     */
    public function __construct(
        \Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory $taxClassKeyFactory,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $quoteDetailsItemFactory,
        \Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory $quoteDetailsFactory,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Magento\Tax\Model\Config $taxConfig
    ) {
        $this->taxClassKeyFactory = $taxClassKeyFactory;
        $this->quoteDetailsItemFactory = $quoteDetailsItemFactory;
        $this->quoteDetailsFactory = $quoteDetailsFactory;
        $this->taxCalculationService = $taxCalculation;
        $this->taxConfig = $taxConfig;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param \Magento\Quote\Model\Quote\Address $billingAddress
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    public function getShippingTaxDetail($quote, $shippingAddress, $billingAddress)
    {
        $storeId = $quote->getStoreId();
        $store = $quote->getStore();
        $item = $this->quoteDetailsItemFactory->create()
            ->setType(CommonTaxCollector::ITEM_TYPE_SHIPPING)
            ->setCode(CommonTaxCollector::ITEM_CODE_SHIPPING)
            ->setQuantity(1);
        $item->setUnitPrice($shippingAddress->getShippingAmount());
        $item->setTaxClassKey(
            $this->taxClassKeyFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue($this->taxConfig->getShippingTaxClass($store))
        )->setIsTaxIncluded(true);
        $quoteDetails = $this->quoteDetailsFactory->create();
        //Set customer tax class
        $quoteDetails->setBillingAddress($billingAddress->getDataModel())
            ->setShippingAddress($shippingAddress->getDataModel())
            ->setCustomerTaxClassKey(
                $this->taxClassKeyFactory->create()
                    ->setType(TaxClassKeyInterface::TYPE_ID)
                    ->setValue($quote->getCustomerTaxClassId())
            );
        $quoteDetails->setItems([$item]);
        $quoteDetails->setCustomerId($quote->getCustomerId());
        $taxDetails = $this->taxCalculationService->calculateTax($quoteDetails, $storeId, true);
        $taxDetailsItem = array_shift($taxDetails->getItems());
        return $taxDetailsItem;
    }
}
