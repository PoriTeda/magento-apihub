<?php

namespace Riki\CatalogPermissions\Model\Indexer\Category\Action;

class Full extends \Magento\CatalogPermissions\Model\Indexer\Category\Action\Full
{
    /**
     * Get permissions columns
     *
     * @return array
     */
    protected function getPermissionColumns()
    {
        $columns = parent::getPermissionColumns();

        foreach ($columns as $k => $v) {
            $columns[$k] = new \Zend_Db_Expr($v);
        }

        return $columns;
    }
}
