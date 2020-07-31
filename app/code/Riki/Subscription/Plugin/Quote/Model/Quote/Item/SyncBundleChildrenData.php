<?php

namespace Riki\Subscription\Plugin\Quote\Model\Quote\Item;

use Magento\Quote\Model\Quote\Item\AbstractItem;

class SyncBundleChildrenData
{
    /**
     * @var array
     */
    protected $copiedFields = [
        'is_spot',
        'is_addition'
    ];

    /**
     * @param AbstractItem $subject
     * @param AbstractItem $result
     * @return AbstractItem
     */
    public function afterSetParentItem(
        AbstractItem $subject,
        AbstractItem $result
    ) {
        if ($subject->getQuote() &&
            $subject->getQuote()->getData('profile_id') &&
            $parentItem = $result->getParentItem()
        ) {
            foreach ($this->copiedFields as $copiedField) {
                $result->setData($copiedField, $parentItem->getData($copiedField));
            }
        }

        return $result;
    }
}
