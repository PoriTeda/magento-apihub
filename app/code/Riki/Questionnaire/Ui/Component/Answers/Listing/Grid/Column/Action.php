<?php
namespace Riki\Questionnaire\Ui\Component\Answers\Listing\Grid\Column;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Action
 * @package Riki\Questionnaire\Ui\Component\Answers\Listing\Grid\Column
 */
class Action extends Column
{
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
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['answer_id'])) {
                continue;
            }

            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('questionnaire/answers/detail', [
                    'answer_id' => $item['answer_id']
                ]),
                'label' => __('View'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('questionnaire/answers/delete', [
                    'answer_id' => $item['answer_id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Are you sure want to do this?'),
                    'message' => __('Are you sure you wan\'t to delete a "%1" item?',['${ $.$data.answer_id }'])
                ]
            ];
        }

        return $dataSource;
    }
}