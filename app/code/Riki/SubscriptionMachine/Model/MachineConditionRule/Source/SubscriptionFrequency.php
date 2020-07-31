<?php

namespace Riki\SubscriptionMachine\Model\MachineConditionRule\Source;

class SubscriptionFrequency extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * SubscriptionFrequency constructor.
     * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
     */
    public function __construct
    (
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
    ) {
        $this->frequencyFactory = $frequencyFactory;

    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $subFrequencyModel = $this->frequencyFactory->create()->getCollection();
        $data = [];
        foreach ($subFrequencyModel as $frequency) {
            $data[] = [
                'label' => $frequency->getData('frequency_interval') . " " . $frequency->getData('frequency_unit'),
                'value' => $frequency->getId()
            ];
        }
        /* your Attribute options list*/
        $this->_options = $data;
        return $this->_options;
    }
}
