<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Checkout\Block\Checkout\Cart;

class Crosssell extends \Magento\TargetRule\Block\Checkout\Cart\Crosssell
{

    public function toHtml()
    {
        /**
         * Remove block crosssell on checkout cart
         *
         */

        $parentName = $this->getLayout()->getParentName('checkout.cart.crosssell');

        if ($parentName =='checkout.cart.container') {
            return '';
        }
        return parent::toHtml();
    }

}
