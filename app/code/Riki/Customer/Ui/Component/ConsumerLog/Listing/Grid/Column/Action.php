<?php
namespace Riki\Customer\Ui\Component\ConsumerLog\Listing\Grid\Column;

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
            if (!isset($item['id'])) {
                continue;
            }

            $item[$name]['view'] = [
                'href' => $this->context->getUrl('customer/consumerlog/view', [
                    'id' => $item['id']
                ]),
                'label' => __('View detail'),
            ];
        }

        return $dataSource;
    }
}