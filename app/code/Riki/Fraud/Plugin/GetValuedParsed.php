<?php
namespace Riki\Fraud\Plugin;

class GetValuedParsed
{
    /**
     * Retrieve parsed value ( this is default magento function, i don't know why Mirasvit overwrite it)
     *
     * @return array|string|int|float
     */
    public function aroundGetValueParsed(\Mirasvit\FraudCheck\Model\Rule\Condition\AbstractCondition $subject, \Closure $proceed)
    {
        if (!$subject->hasValueParsed()) {
            $value = $subject->getData('value');
            if (is_array($value) && isset($value[0]) && is_string($value[0])) {
                $value = $value[0];
            }
            if ($subject->isArrayOperatorType() && $value) {
                $value = preg_split('#\s*[,;]\s*#', $value, null, PREG_SPLIT_NO_EMPTY);
            }
            $subject->setValueParsed($value);
        }
        return $subject->getData('value_parsed');
    }
}