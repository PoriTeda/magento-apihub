<?php

namespace Riki\SalesRule\Model\Rule\Condition\Product;

class Found extends \Magento\SalesRule\Model\Rule\Condition\Product\Found
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
    )
    {
        $this->eventManager = $eventManager;

        parent::__construct(
            $context,
            $ruleConditionProduct,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $model = $this->prepareDataModelForValidate($model);

        return parent::validate($model);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return mixed
     */
    public function prepareDataModelForValidate(\Magento\Framework\Model\AbstractModel $model)
    {
        $validateData = new \Magento\Framework\DataObject(['model' => clone $model]);

        $this->eventManager->dispatch('riki_salesrule_condition_product_validate_before', [
            'original_model' => $model,
            'validate_data' => $validateData,
        ]);

        return $validateData->getData('model');
    }
}
