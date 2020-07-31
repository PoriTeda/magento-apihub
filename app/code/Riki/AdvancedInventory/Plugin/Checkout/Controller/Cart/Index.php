<?php
namespace Riki\AdvancedInventory\Plugin\Checkout\Controller\Cart;

class Index
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /* @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
    }

    public function aroundExecute(
        \Magento\Checkout\Controller\Cart\Index $subject,
        \Closure $proceed
    ){
        $quote = $this->checkoutSession->getQuote();
        $existProductHaveQtyMoreThanOneHundred = false;
        foreach ($quote->getAllVisibleItems() as $item) {
            if (strtoupper($item['case_display'])
                == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $qtyShowInFo = $item->getQty() / $item->getUnitQty();
                if ($qtyShowInFo >= 100) {
                    $existProductHaveQtyMoreThanOneHundred = true;
                    break;
                }
            } else {
                if ($item->getQty() >= 100) {
                    $existProductHaveQtyMoreThanOneHundred = true;
                    break;
                }
            }
        }

        if ($existProductHaveQtyMoreThanOneHundred) {
            $this->messageManager->addWarning(__('1 is displayed when the quantity of one item is 100 or more, but the actual customer order quantity is recognized correctly by the system side. The order quantity is correctly displayed on the "Order details confirmation" screen.'));
        }
        return $proceed();
    }
}