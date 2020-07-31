<?php
namespace Riki\Rma\Model\ResourceModel\ReviewCc\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'Riki\Rma\Model\ReviewCc\Item',
            'Riki\Rma\Model\ResourceModel\ReviewCc\Item'
        );
    }

    /**
     * add review cc to filter
     *
     * @param $reviewCc
     * @return $this
     */
    public function setReviewCcFilter($reviewCc)
    {
        if ($reviewCc instanceof \Riki\Rma\Model\ReviewCc) {
            $reviewCcId = $reviewCc->getId();
            if ($reviewCcId) {
                $this->addFieldToFilter('review_id', $reviewCcId);
            } else {
                $this->_totalRecords = 0;
                $this->_setIsLoaded(true);
            }
        } else {
            $this->addFieldToFilter('review_id', $reviewCc);
        }
        return $this;
    }
}
