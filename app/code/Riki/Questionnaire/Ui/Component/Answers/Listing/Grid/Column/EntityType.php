<?php

namespace Riki\Questionnaire\Ui\Component\Answers\Listing\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class EntityType extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * EntityType constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item['entity_type'])) {
                switch ($item['entity_type']) {
                    case \Riki\Questionnaire\Model\Questionnaire::CHECKOUT_QUESTIONNAIRE:
                        $item['entity_type'] = __('Checkout Questionnaire');
                        break;
                    case  \Riki\Questionnaire\Model\Questionnaire::DISENGAGEMENT_QUESTIONNAIRE:
                        $item['entity_type'] = __('Disengagement Questionnaire');
                        break;
                    default:
                        $item['entity_type'] = __('Checkout Questionnaire');
                        break;
                }
            }
        }

        return $dataSource;
    }
}
