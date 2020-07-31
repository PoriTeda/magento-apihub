<?php
namespace Riki\Checkout\Observer;


class MultishippingOrderAddress implements \Magento\Framework\Event\ObserverInterface
{
    protected $addressItemRelationship;
    protected $eventManager;

    /**
     * @var \Riki\Checkout\Plugin\Quote\Model\Quote\Item\QtyCombine
     */
    protected $qtyCombinePlugin;

    /**
     * MultishippingOrderAddress constructor.
     *
     * @param \Riki\Checkout\Plugin\Quote\Model\Quote\Item\QtyCombine $qtyCombinePlugin
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Riki\Checkout\Model\AddressItemRelationship $addressItemRelationship
     */
    public function __construct(
        \Riki\Checkout\Plugin\Quote\Model\Quote\Item\QtyCombine $qtyCombinePlugin,
        \Magento\Framework\Event\Manager $eventManager,
        \Riki\Checkout\Model\AddressItemRelationship $addressItemRelationship
    ) {
        $this->qtyCombinePlugin = $qtyCombinePlugin;
        $this->eventManager = $eventManager;
        $this->addressItemRelationship = $addressItemRelationship;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $order = $observer->getData('order');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        /*Simulate doesn't need to update multiple shipping address */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }
        foreach ($quote->getAllItems() as $item) {
            $this->qtyCombinePlugin->cleanCacheByIds([$item->getId()]);
        }

        if ($order->getData('is_multiple_shipping') || $quote->isMultipleShippingAddresses()) {
            $this->eventManager->dispatch('after_save_address_item_in_multi_checkout', [
                'order' => $order
            ]);
        }
    }

}