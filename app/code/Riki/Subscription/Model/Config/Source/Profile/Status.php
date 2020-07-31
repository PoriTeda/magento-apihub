<?php

namespace Riki\Subscription\Model\Config\Source\Profile;

class Status implements \Magento\Framework\Option\ArrayInterface
{
    const DISABLED = 0;
    const ON_GOING = 1;
    const COMPLETED = 2;

    /**
     * @var array
     */
    protected $statuses = [
        self::ON_GOING  =>  'Ongoing',
        self::COMPLETED =>  'Completed',
        self::DISABLED  =>  'Disengaged'
    ];

    /**
     * @return array
     */
    public function getStatuses()
    {
        $result = [];
        foreach ($this->statuses as $code => $label) {
            $result[$code] = __($label);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->getStatuses();

        $result = [];
        foreach ($statuses as $key => $label) {
            $result[] = ['value' => $key, 'label' => $label];
        }
        return $result;
    }
}
