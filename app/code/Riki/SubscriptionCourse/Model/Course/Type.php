<?php

namespace Riki\SubscriptionCourse\Model\Course;

use Magento\Framework\Data\OptionSourceInterface;

class Type implements OptionSourceInterface
{
    /**#@+
     * Available course types
     */
    const TYPE_SUBSCRIPTION = 'subscription';

    const TYPE_HANPUKAI = 'hanpukai';

    const TYPE_HANPUKAI_FIXED = 'hfixed';

    const TYPE_HANPUKAI_SEQUENCE = 'hsequence';

    const TYPE_MULTI_MACHINES = 'multimachine';

    const TYPE_MONTHLY_FEE = 'monthly_fee';

    const TYPE_ORDER_SPOT = 'SPOT';

    const TYPE_ORDER_HANPUKAI = 'HANPUKAI';

    const TYPE_ORDER_SUBSCRIPTION = 'SUBSCRIPTION';

    const TYPE_ORDER_DELAY_PAYMENT = 'DELAY PAYMENT';

    /**#@-*/

    /**
     * Default course type
     */
    const DEFAULT_TYPE = 'subscription';

    /**
     * Get course type labels array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $options = [];
        foreach ($this->getTypes() as $typeId => $type) {
            $options[$typeId] = (string)$type['label'];
        }
        return $options;
    }

    /**
     * Get course type labels array with empty value
     *
     * @return array
     */
    public function getAllOption()
    {
        $options = $this->getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }

    /**
     * Get course type labels array with empty value for option element
     *
     * @return array
     */
    public function getAllOptions()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    /**
     * Get course type labels array for option element
     *
     * @return array
     */
    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * Get course type label
     *
     * @param string $optionId
     * @return null|string
     */
    public function getOptionText($optionId)
    {
        $options = $this->getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * Get course types
     *
     * @return array
     */
    public function getTypes()
    {
        return [
            self::TYPE_SUBSCRIPTION   =>  __('Subscription Course'),
            self::TYPE_HANPUKAI    =>  __('Hanpukai')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }

    public function getHanpukaiTypes()
    {
        return [
            self::TYPE_HANPUKAI_FIXED   =>  __('Hanbukai Fixed'),
            self::TYPE_HANPUKAI_SEQUENCE    =>  __('Hanpukai Sequence')
        ];
    }

    /**
     * @return array
     */
    public function getAllOrderTypeOptions()
    {
        return [
            self::TYPE_ORDER_SPOT  =>  'SPOT',
            self::TYPE_SUBSCRIPTION  =>  'SUBSCRIPTION',
            self::TYPE_ORDER_HANPUKAI  =>  'HANPUKAI'
        ];
    }
}
