<?php
namespace Riki\Checkout\Plugin\CatalogInventory\Model\Quote\Item;

/**
 * @toto should move this into general module, may be Riki_Framework
 */
class QuantityValidator
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * QuantityValidator constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Capture function
     *
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return mixed[]
     */
    public function aroundValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        $key = \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator::class . '::validate';
        $this->registry->unregister($key);
        $this->registry->register($key, [
            'observer' => $observer
        ]);
        $result = $proceed($observer);
        $this->registry->unregister($key);

        return $result;
    }
}