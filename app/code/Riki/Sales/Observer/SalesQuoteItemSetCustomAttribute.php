<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesQuoteItemSetCustomAttribute
 * @package Riki\Sales\Observer
 */
class SalesQuoteItemSetCustomAttribute implements ObserverInterface
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * SalesQuoteItemSetCustomAttribute constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();
        $product = $observer->getProduct();
        if (!$product->getSalesOrganization()) {
            // NED-2990 - reload product in order to supply sales_organization attribute
            $bundleItemProduct = $this->productRepository->get($quoteItem->getSku());
            $this->setSalesOrganizationToQuoteItem($quoteItem, $bundleItemProduct);
        } else {
            $this->setSalesOrganizationToQuoteItem($quoteItem, $product);
        }
    }

    /**
     * Set sales organization to quote item
     * @param $quoteItem
     * @param $product
     */
    private function setSalesOrganizationToQuoteItem($quoteItem, $product)
    {
        $salesOrg = $product->getResource()->getAttribute('sales_organization');
        if ($salesOrg) {
            if ($salesOrgSelected = $product->getSalesOrganization()) {
                $salesOrgSelectedText = $salesOrg->getSource()->getOptionText($salesOrgSelected);
                if (is_string($salesOrgSelectedText)) {
                    $quoteItem->setSalesOrganization($salesOrgSelectedText);
                }
            }
        }
    }
}
