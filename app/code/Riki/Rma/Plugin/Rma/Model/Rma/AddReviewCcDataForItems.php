<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

use Riki\Rma\Model\Config\Source\Rma\MassAction;

class AddReviewCcDataForItems
{
    /**
     * @var \Riki\Rma\Model\ReviewCcManagement
     */
    protected $reviewCcManagement;

    /**
     * AddCustomDataForItems constructor.
     * @param \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement
     */
    public function __construct(
        \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement
    ) {
        $this->reviewCcManagement = $reviewCcManagement;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @param \Magento\Rma\Model\ResourceModel\Item\Collection $result
     * @return \Magento\Rma\Model\ResourceModel\Item\Collection
     */
    public function afterGetItemsForDisplay(
        \Magento\Rma\Model\Rma $rma,
        \Magento\Rma\Model\ResourceModel\Item\Collection $result
    ) {
        if ($rma->getData('mass_action') == MassAction::REVIEW_BY_CC) {
            $defaultValues = $this->reviewCcManagement->getDefaultItemValues();
            /** @var \Magento\Rma\Model\Item $item */
            foreach ($result as $item) {
                foreach ($defaultValues as $key => $value) {
                    $item->setData($key, $value);
                }
            }
        }

        return $result;
    }
}