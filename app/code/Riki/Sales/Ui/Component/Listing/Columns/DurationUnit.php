<?php

namespace Riki\Sales\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;

class DurationUnit extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     * @var \Riki\SubscriptionFrequency\Model\Source\FrequencyUnit
     */
    protected $_frequencyUnit;

    /**
     * DurationUnit constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param \Riki\SubscriptionFrequency\Model\Source\FrequencyUnit $frequencyUnit
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        \Riki\SubscriptionFrequency\Model\Source\FrequencyUnit $frequencyUnit,
        array $components = [],
        array $data = []
    ) {
        $this->_frequencyUnit = $frequencyUnit;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        $frequencyUnitNames = [];
        $availableOptions = $this->_frequencyUnit->toOptionArray();
        foreach ($availableOptions as $frequency) {
            $frequencyUnitNames[$frequency['value']] = $frequency['label'];
        }
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $frequencyUnits = [];
                if(!empty($item[$fieldName])){
                    $fieldValue = explode(',', (string)$item[$fieldName]);
                    foreach ($fieldValue as $frequencyId) {
                        if(isset($frequencyUnitNames[$frequencyId]))
                            $frequencyUnits[] = $frequencyUnitNames[$frequencyId];
                    }
                    $item[$fieldName] = implode(', ', $frequencyUnits);
                }
            }
        }

        return $dataSource;
    }
}
