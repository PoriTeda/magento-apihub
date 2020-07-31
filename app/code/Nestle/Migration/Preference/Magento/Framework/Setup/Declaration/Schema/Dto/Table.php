<?php


namespace Nestle\Migration\Preference\Magento\Framework\Setup\Declaration\Schema\Dto;


use Nestle\Migration\Model\DataMigration;

class Table extends \Magento\Framework\Setup\Declaration\Schema\Dto\Table
{
    /**
     * @inheritdoc
     */
    public function getDiffSensitiveParams()
    {
        if(!DataMigration::$IS_SUPPORT_MEMORY_ENGINE && $this->getEngine() == "memory"){
            return [
                'resource'  => $this->getResource(),
                'engine'    => "innodb",
                'comment'   => $this->getComment(),
                'charset'   => $this->getCharset(),
                'collation' => $this->getCollation()
            ];
        }

        return [
            'resource'  => $this->getResource(),
            'engine'    => $this->getEngine(),
            'comment'   => $this->getComment(),
            'charset'   => $this->getCharset(),
            'collation' => $this->getCollation()
        ];
    }
}
