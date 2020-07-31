<?php
/**
* Copyright © 2018 @Nestle Japan Limited
*
*/
namespace Riki\SubscriptionPage\Block\Catalog\Product\View\Type\Bundle\Option;

/**
 * Workaround for Full page cache
 * @package Riki\SubscriptionPage\Block\Catalog\Product\View\Type\Bundle\Checkbox
 */
class Checkbox extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option\Checkbox
{
    /**
     * Workaround for Magento issue empty product registry on listing display
     *
     * @return array
     */
    public function getIdentities()
    {
        // @TODO: find a better way than using random number for entities
        // although the block would be clean by the commandline clean cache by tags but it could not be clean particularly
        $random = rand(0,99999);
        return $identities = [\Magento\Catalog\Model\Product::CACHE_TAG . '_' . $random];
    }

}
