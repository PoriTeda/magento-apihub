<?php
namespace Riki\Subscription\Model\Emulator\Cart\Item;

use \Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;

class Processor
    extends \Magento\Quote\Model\Quote\Item\Processor
{
    public function __construct(
        ItemFactory $quoteItemFactory,
        StoreManagerInterface $storeManager,
        State $appState,
        \Riki\Subscription\Model\Emulator\Cart\ItemFactory $emulatorCartItemFactory
    )
    {
        parent::__construct($quoteItemFactory, $storeManager, $appState);
        $this->quoteItemFactory = $emulatorCartItemFactory;
    }
}