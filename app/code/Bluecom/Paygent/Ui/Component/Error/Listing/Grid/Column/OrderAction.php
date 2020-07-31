<?php
namespace Bluecom\Paygent\Ui\Component\Error\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class OrderAction extends Column
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
            if (!isset($item['entity_id'])) {
                continue;
            }

            $item[$name]['view'] = [
                'href' => $this->context->getUrl('sales/order/view', [
                    'order_id' => $item['entity_id']
                ]),
                'label' => __('View Order'),
            ];
        }

        return $dataSource;
    }
}