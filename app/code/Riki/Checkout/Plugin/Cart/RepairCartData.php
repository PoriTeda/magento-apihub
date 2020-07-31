<?php

namespace Riki\Checkout\Plugin\Cart;

class RepairCartData
{
    /* @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /**
     * CheckApplicationLimitCart constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Before Execute
     * @return void
     */
    public function beforeExecute()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getItems() and $quote->getItemsCount() > 0) {
            $quote->collectTotals()->save();
            $this->checkoutSession->setAmpromoItems(['_groups' => []]);
        }
    }
}
