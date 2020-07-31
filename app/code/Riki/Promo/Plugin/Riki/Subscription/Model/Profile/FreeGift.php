<?php
namespace Riki\Promo\Plugin\Riki\Subscription\Model\Profile;

use Magento\Backend\App\Area\FrontNameResolver;

class FreeGift
{
    /**
     * @var \Riki\Promo\Helper\Data
     */
    private $promoDataHelper;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * FreeGift constructor.
     * @param \Riki\Promo\Helper\Data $dataHelper
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Riki\Promo\Helper\Data $dataHelper,
        \Magento\Framework\App\State $state
    ) {
        $this->promoDataHelper = $dataHelper;
        $this->state = $state;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\FreeGift $subject
     * @param $cartItems
     * @param $giftItems
     * @return array
     */
    public function beforeAddFreeGiftsToCartProfile(
        \Riki\Subscription\Model\Profile\FreeGift $subject,
        $cartItems,
        $giftItems
    ) {
        if ($this->state->getAreaCode() !== FrontNameResolver::AREA_CODE) {
            /** @var \Riki\Subscription\Model\Emulator\Order\Item $orderItem */
            foreach ($giftItems as $index => $orderItem) {
                if (!$this->promoDataHelper->isVisibleFreeGiftOrderItem($orderItem)) {
                    unset($giftItems[$index]);
                }
            }
        }

        return [$cartItems, $giftItems];
    }
}
