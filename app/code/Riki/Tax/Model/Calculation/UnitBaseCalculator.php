<?php

namespace Riki\Tax\Model\Calculation;

use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use \Riki\GiftWrapping\Model\Total\Quote\Tax\Giftwrapping;

class UnitBaseCalculator extends \Magento\Tax\Model\Calculation\UnitBaseCalculator
{
    /**
     * Round amount
     *
     * @param float $amount
     * @param null|float $rate
     * @param null|bool $direction
     * @param string $type
     * @param bool $round
     * @param QuoteDetailsItemInterface $item
     * @return float
     */
    protected function roundAmount(
        $amount,
        $rate = null,
        $direction = null,
        $type = self::KEY_REGULAR_DELTA_ROUNDING,
        $round = true,
        $item = null
    ) {
        if ($item->getAssociatedItemCode()) {
            // Use delta rounding of the product's instead of the weee's
            $type = $type . $item->getAssociatedItemCode();

            /*improve calculate tax flow to avoid sub additional tax from product will be added for gift wrapping*/
            if ($item->getType() == Giftwrapping::ITEM_TYPE) {
                return $this->deltaRoundGw($amount, $rate, $direction, $type, $round);
            }
        } else {
            $type = $type . $item->getCode();
        }
        return $this->deltaRound($amount, $rate, $direction, $type, $round);
    }

    /**
     * Round price for gift wrapping tax
     *   based on detalRound function,
     *   but remove the step that added sub tax (from delta) to gift wrapping tax
     *
     * @param float $price
     * @param string $rate
     * @param bool $direction
     * @param string $type
     * @param bool $round
     * @return float
     */
    protected function deltaRoundGw($price, $rate, $direction, $type = self::KEY_REGULAR_DELTA_ROUNDING, $round = true)
    {
        if ($price) {
            $rate = (string)$rate;
            $type = $type . $direction;
            $roundPrice = $price;
            if ($round) {
                $roundPrice = $this->calculationTool->round($roundPrice);
            }
            $this->roundingDeltas[$type][$rate] = $price - $roundPrice;
            $price = $roundPrice;
        }
        return $price;
    }
}
