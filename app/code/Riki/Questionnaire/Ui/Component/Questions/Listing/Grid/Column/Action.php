<?php
namespace Riki\Questionnaire\Ui\Component\Questions\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Action extends Column
{
    /**
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
            if (!isset($item['enquete_id'])) {
                continue;
            }

            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('questionnaire/questions/edit', [
                    'enquete_id' => $item['enquete_id']
                ]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('questionnaire/questions/delete', [
                    'enquete_id' => $item['enquete_id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Are you sure want to do this?'),
                    'message' => __('Are you sure you wan\'t to delete a "%1" item?',['${ $.$data.enquete_id }'])
                ]
            ];
        }

        return $dataSource;
    }
}