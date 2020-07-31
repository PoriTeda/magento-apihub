<?php
namespace Riki\Catalog\Observer;

class SapProductBeforeSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * SapProductBeforeSaveObserver constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }


    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getDataObject();
        if (!$product instanceof \Magento\Catalog\Model\Product || !$product->getId()) {
            return;
        }

        $currentProduct = $this->registry->registry(\Riki\Catalog\Model\SapProductRepository::CURRENT_PRODUCT);
        if (!$currentProduct) {
            return;
        }

        if ((isset($currentProduct['id']) && $currentProduct['id'] == $product->getId())
            && isset($currentProduct['website_ids'])
        ) {
            $product->setWebsiteIds($currentProduct['website_ids']);
        }
    }
}