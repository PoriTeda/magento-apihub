<?php
namespace Riki\Sales\Model\ResourceModel\Order\Shipment\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Riki\SapIntegration\Model\Api\Shipment;

class Collection extends SearchResult
{

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['sap_export' => $this->getTable('riki_shipment_sap_exported')],
            'sap_export.shipment_entity_id = main_table.entity_id',
            [
                'm_export_sap_date'  =>  'export_sap_date'
            ]
        );

        $connection = $this->getConnection();

        $expression = $connection->getCheckSql(
            'sap_export.is_exported_sap IS NOT NULL',
            'sap_export.is_exported_sap',
            $connection->quote(Shipment::FLAG_NEVER_EXPORT)
        );
        $this->getSelect()->columns(['m_is_exported_sap' => $expression]);

        return $this;
    }

    /**
     * @param array|string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'm_is_exported_sap') {
            $field = 'sap_export.is_exported_sap';
            if (is_array($condition) && isset($condition['eq'])) {
                if ($condition['eq'] == Shipment::FLAG_NEVER_EXPORT) {
                    $field = [
                        'sap_export.is_exported_sap',
                        'sap_export.is_exported_sap'
                    ];

                    $condition = [
                        ['gt' => 0],
                        ['null' => true]
                    ];
                }
            }
        }
        else{
            if(is_string($field)){
                $field = "main_table.". $field;
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
