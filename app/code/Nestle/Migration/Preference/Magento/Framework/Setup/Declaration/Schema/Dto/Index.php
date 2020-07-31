<?php


namespace Nestle\Migration\Preference\Magento\Framework\Setup\Declaration\Schema\Dto;


use Nestle\Migration\Model\DataMigration;

class Index extends \Magento\Framework\Setup\Declaration\Schema\Dto\Index
{
    /**
     * {@inheritdoc}
     */
    public function getDiffSensitiveParams()
    {
        if (!DataMigration::$IS_SUPPORT_MEMORY_ENGINE && $this->getIndexType() == "hash") {
            return [
                'type'      => $this->getType(),
                'columns'   => $this->getColumnNames(),
                'indexType' => "btree"
            ];
        }
        return [
            'type'      => $this->getType(),
            'columns'   => $this->getColumnNames(),
            'indexType' => $this->getIndexType()
        ];
    }
}
