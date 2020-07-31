<?php
namespace Riki\ThirdPartyImportExport\Model\ResourceModel\Rma;

class Shipping extends \Magento\Rma\Model\ResourceModel\Shipping
{
    /**
     * Get Carrier data for rma item
     *
     * @param $rmaId
     * @param $columns
     * @return array
     */
    public function getCarrierByRmaId($rmaId, $columns)
    {
        $connection = $this->getConnection();

        /*get carrier query*/
        $queryBuilder = $connection->select()->from(
            $this->_mainTable, $columns
        )->where(
            'rma_entity_id = ?', $rmaId
        )->limitPage(1, 1)->limit(1);

        return $connection->fetchRow($queryBuilder);
    }
}
