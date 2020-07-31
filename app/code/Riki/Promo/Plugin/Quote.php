<?php
namespace Riki\Promo\Plugin;

class Quote
{
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $promoDataHelper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Amasty\Promo\Helper\Item $itemHelper
     * @param \Riki\Promo\Helper\Data $dataHelper
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Amasty\Promo\Helper\Item $itemHelper,
        \Riki\Promo\Helper\Data $dataHelper,
        \Magento\Framework\App\State $appState
    ) {
        $this->promoItemHelper = $itemHelper;
        $this->promoDataHelper = $dataHelper;
        $this->appState = $appState;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllVisibleItems(
        \Magento\Quote\Model\Quote $subject,
        array $result
    ) {

        if ($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            return $result;
        }

        $newResult = [];

        foreach ($result as $item) {
            $ruleId = $this->promoItemHelper->getRuleId($item);

            if ($ruleId) {
                if ($this->promoDataHelper->isFreeGiftVisibleInCart($ruleId)) {
                    $newResult[] = $item;
                }
            } else {
                $newResult[] = $item;
            }
        }

        return $newResult;
    }
}
