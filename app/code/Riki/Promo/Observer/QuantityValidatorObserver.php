<?php
namespace Riki\Promo\Observer;

class QuantityValidatorObserver extends \Magento\CatalogInventory\Observer\QuantityValidatorObserver
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * QuantityValidatorObserver constructor.
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $quantityValidator
     * @param \Riki\Promo\Helper\Item $promoItemHelper
     */
    public function __construct(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $quantityValidator,
        \Riki\Promo\Helper\Item $promoItemHelper
    ) {
        $this->promoItemHelper = $promoItemHelper;

        parent::__construct($quantityValidator);
    }

    /**
     * Do not validate qty for free gift
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$this->promoItemHelper->isPromoItem($quoteItem)) {
            parent::execute($observer);
        }
    }
}
