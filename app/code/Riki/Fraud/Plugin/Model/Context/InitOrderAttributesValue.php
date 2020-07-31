<?php
namespace Riki\Fraud\Plugin\Model\Context;

use Mirasvit\FraudCheck\Model\Context;
use Mirasvit\FraudCheck\Model\Rule\Condition\Order as OrderRuleCondition;

class InitOrderAttributesValue
{
    /**
     * @var OrderRuleCondition
     */
    protected $orderRuleCondition;

    /**
     * InitOrderAttributesValue constructor.
     * @param OrderRuleCondition $orderRuleCondition
     */
    public function __construct(
        OrderRuleCondition $orderRuleCondition
    ) {
        $this->orderRuleCondition = $orderRuleCondition;
    }

    /**
     * Set default data for all of attribute to avoid order load again when validate rule
     *
     * @param Context $context
     * @param $result
     * @return mixed
     */
    public function afterExtractOrderData(Context $context, $result)
    {
        $attributes = $this->orderRuleCondition->getAttributeOption();

        $order = $context->order;

        foreach ($attributes as $attribute => $label) {
            if (!$order->hasData($attribute)) {
                $order->setData($attribute, null);
            }
        }

        return $result;
    }
}