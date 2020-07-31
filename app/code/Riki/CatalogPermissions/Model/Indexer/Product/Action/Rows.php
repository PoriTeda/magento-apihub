<?php
/**
 * CatalogPermissions
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CatalogPermissions
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CatalogPermissions\Model\Indexer\Product\Action;

use Magento\CatalogPermissions\Model\Indexer\Product\Action\Rows as MagentoRows;

/**
 * Rows
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CatalogPermissions
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Rows extends MagentoRows
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