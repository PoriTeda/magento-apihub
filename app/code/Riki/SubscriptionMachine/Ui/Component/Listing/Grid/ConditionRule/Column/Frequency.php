<?php

namespace Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Frequency
 * @package Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column
 */
class Frequency extends Column
{
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * System store
     *
     * @var SystemStore
     */
    protected $systemStore;

    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemStore $systemStore
     * @param Escaper $escaper
     * @param \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        Escaper $escaper,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory,
        array $components = [],
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->escaper = $escaper;
        $this->frequencyFactory = $frequencyFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item[$this->getData('name')]);
            }
        }

        return $dataSource;
    }

    /**
     * Prepare Item
     *
     * @param string $frequency
     * @return string
     */
    public function prepareItem($frequency)
    {
        $frequency = \Zend_Json::decode($frequency);
        if (sizeof($frequency) > 0) {
            $frequencyModel = $this->frequencyFactory->create()->getCollection();
            if (sizeof($frequency) > 0) {
                $frequencyModel->addFieldToFilter('frequency_id', $frequency);
            }
            $frequencyLabel = [];
            foreach ($frequencyModel as $item) {
                $frequencyLabel[] = $item->getData('frequency_interval') . " " . $item->getData('frequency_unit');
            }
            return implode(', ', $frequencyLabel);
        }

        return null;
    }
}
