<?php

namespace Riki\SalesRule\Model\Rule\Condition\Product;

use Magento\SalesRule\Model\Rule\Condition\Product\Combine;

class Subselect extends \Magento\SalesRule\Model\Rule\Condition\Product\Subselect
{
    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * Found constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\SalesRule\Model\Rule\Condition\Product $ruleConditionProduct,
        \Magento\Framework\Event\Manager $eventManager,
        array $data = []
    ) {
        $this->eventManager = $eventManager;

        parent::__construct(
            $context,
            $ruleConditionProduct,
            $data
        );
    }

    /**
     * Need to prepare items (OOS, free gift, ...) before use to validate
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $validateData = new \Magento\Framework\DataObject(['model' => clone $model]);

        $this->eventManager->dispatch('riki_salesrule_condition_product_validate_before', [
            'original_model' => $model,
            'validate_data' => $validateData,
        ]);

        $model = $validateData->getData('model');

        if (!$this->getConditions()) {
            return false;
        }
        $attr = $this->getAttribute();
        $total = 0;
        foreach ($model->getQuote()->getAllVisibleItems() as $item) {
            if (Combine::validate($item)) {
                $total += $item->getData($attr);
            }

            $item->setData('is_subselect_validated', true);
        }

        foreach ($model->getAllVisibleItems() as $item) {
            if (!$item->getData('is_subselect_validated')
                && Combine::validate($item)
            ) {
                $total += $item->getData($attr);
            }
        }
        return $this->validateAttribute($total);
    }
}
