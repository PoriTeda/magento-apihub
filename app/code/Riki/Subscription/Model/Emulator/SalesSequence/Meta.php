<?php

namespace Riki\Subscription\Model\Emulator\SalesSequence;

use Riki\Subscription\Model\Emulator\Config;

class Meta extends \Magento\SalesSequence\Model\Meta
{

    /**
     * @var bool
     */
    protected $checkTmpTableCreated = false;

    /**
     * @param $value
     */
    public function setCheckTempTable($value){
        $this->checkTmpTableCreated = $value;
    }

    /**
     * @return string
     */
    public function getSequenceTable()
    {
        $sourceTable = $this->getData('sequence_table');

        $this->_createTmpTable($sourceTable);

        return $this->_getTmpTableName($sourceTable);
    }

    /**
     * @param $sourceTable
     * @return string
     */
    protected function _getTmpTableName($sourceTable)
    {
        return Config::TMP_TABLE_PREFIX . $sourceTable . Config::TMP_TABLE_SUFFIX;
    }

    /**
     * @param $sourceTable
     * @return int
     */
    protected function _getLastSequenceNumber($sourceTable)
    {
        $connection = $this->getResource()->getConnection();

        $select = $connection->select()
            ->from($sourceTable, [new \Zend_Db_Expr('MAX(sequence_value)')]);

        $lastSequenceValue = (int)$connection->fetchOne($select);

        return ++$lastSequenceValue;
    }

    /**
     * @param $sourceTableName
     */
    protected function _createTmpTable($sourceTableName)
    {
        if($this->checkTmpTableCreated){

            $tmpTableName = $this->_getTmpTableName($sourceTableName);

            $connection = $this->getResource()->getConnection();

            $connection->createTemporaryTableLike($tmpTableName,$sourceTableName,true);
        }
    }
}