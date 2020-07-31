<?php
namespace Riki\AdminLog\Ui\Component\Log\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Ip extends Column
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
        foreach ($dataSource['data']['items'] as $key=> &$item) {
            if(isset($item['ip']) && $item['ip'] !=''){
                $item['ip'] = long2ip($item['ip']);
            }
        }
        return $dataSource;
    }
}