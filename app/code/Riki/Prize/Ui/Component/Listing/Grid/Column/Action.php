<?php
namespace Riki\Prize\Ui\Component\Listing\Grid\Column;

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
            if (!isset($item['prize_id'])) {
                continue;
            }

            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('prize/index/edit', [
                    'id' => $item['prize_id']
                ]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('prize/index/delete', [
                    'id' => $item['prize_id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Are you sure want to do this?'),
                    'message' => __('Are you sure you wan\'t to delete a "%1" item?',['${ $.$data.prize_id }'])
                ]
            ];
        }

        return $dataSource;
    }
}