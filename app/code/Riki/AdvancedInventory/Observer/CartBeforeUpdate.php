<?php

namespace Riki\AdvancedInventory\Observer;

class CartBeforeUpdate implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * CartBeforeUpdate constructor
     *
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Riki\Catalog\Model\StockState $stockState
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Riki\Catalog\Model\StockState $stockState
    ) {
        $this->messageManager = $messageManager;
        $this->stockState = $stockState;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $cart = $observer->getEvent()->getCart();
        $quote = $cart->getQuote();
        $data = $observer->getEvent()->getInfo()->toArray();
        
        foreach ($data as $itemId => $itemInfo) {
            if ($item = $quote->getItemById($itemId)) {
                $product = $item->getProduct();

                /*validate stock before update*/
                $canAssigned = $this->stockState->canAssigned(
                    $product,
                    $itemInfo['qty'],
                    $this->stockState->getPlaceIds()
                );

                if (!$canAssigned) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('We don\'t have as many "%1" as you requested.', $product->getName())
                    );
                }
            }
        }
        return $this;
    }
}

