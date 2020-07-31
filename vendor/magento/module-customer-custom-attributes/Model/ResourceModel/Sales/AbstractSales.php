<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\ResourceModel\Sales;

/**
 * Customer Sales abstract resource
 */
abstract class AbstractSales extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Used us prefix to name of column table
     *
     * @var null | string
     */
    protected $_columnPrefix = 'customer';

    /**
     * Primary key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Return column name for attribute
     *
     * @param \Magento\Customer\Model\Attribute $attribute
     * @return string
     */
    protected function _getColumnName(\Magento\Customer\Model\Attribute $attribute)
    {
        $columnName = $attribute->getAttributeCode();
        if ($this->_columnPrefix) {
            $columnName = sprintf('%s_%s', $this->_columnPrefix, $columnName);
        }
        return $columnName;
    }

    /**
     * Saves a new attribute
     *
     * @param \Magento\Customer\Model\Attribute $attribute
     * @return $this
     */
    public function saveNewAttribute(\Magento\Customer\Model\Attribute $attribute)
    {
        $backendType = $attribute->getBackendType();
        if ($backendType == \Magento\Customer\Model\Attribute::TYPE_STATIC) {
            return $this;
        }

        switch ($backendType) {
            case 'datetime':
                $definition = ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATE];
                break;
            case 'decimal':
                $definition = ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, 'length' => '12,4'];
                break;
            case 'int':
                $definition = ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER];
                break;
            case 'text':
                $definition = ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT];
                break;
            case 'varchar':
                $definition = ['type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 'length' => 255];
                break;
            default:
                return $this;
        }

        $columnName = $this->_getColumnName($attribute);
        $definition['comment'] = ucwords(str_replace('_', ' ', $columnName));
        $this->getConnection()->addColumn($this->getMainTable(), $columnName, $definition);

        return $this;
    }

    /**
     * Deletes an attribute
     *
     * @param \Magento\Customer\Model\Attribute $attribute
     * @return $this
     */
    public function deleteAttribute(\Magento\Customer\Model\Attribute $attribute)
    {
        $this->getConnection()->dropColumn($this->getMainTable(), $this->_getColumnName($attribute));
        return $this;
    }

    /**
     * Return resource model of the main entity
     *
     * @return null
     */
    protected function _getParentResourceModel()
    {
        return null;
    }

    /**
     * Check if main entity exists in main table.
     * Need to prevent errors in case of multiple customer log in into one account.
     *
     * @param \Magento\CustomerCustomAttributes\Model\Sales\AbstractSales $sales
     * @return bool
     */
    public function isEntityExists(\Magento\CustomerCustomAttributes\Model\Sales\AbstractSales $sales)
    {
        if (!$sales->getId()) {
            return false;
        }

        $resource = $this->_getParentResourceModel();
        if (!$resource) {
            /**
             * If resource model is absent, we shouldn't check the database for if main entity exists.
             */
            return true;
        }

        $parentTable = $resource->getMainTable();
        $parentIdField = $resource->getIdFieldName();
        $select = $this->getConnection()->select()->from(
            $parentTable,
            $parentIdField
        )->forUpdate(
            true
        )->where(
            "{$parentIdField} = ?",
            $sales->getId()
        );
        if ($this->getConnection()->fetchOne($select)) {
            return true;
        }
        return false;
    }
}
