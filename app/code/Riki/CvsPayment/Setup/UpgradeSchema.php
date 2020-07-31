<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Setup;

use Magento\Framework\DB\Ddl\Table;

/**
 * Class UpgradeSchema
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema
    extends \Riki\Framework\Setup\Version\Schema
    implements \Magento\Framework\Setup\UpgradeSchemaInterface
{
    /**
     * Version 1.0.2
     *
     * @return void
     */
    public function version102()
    {
        $this->addColumn(
            'sales_order',
            'csv_start_date',
            [
                'type' => Table::TYPE_DATETIME,
                'comment' => 'This field is used for detect orders '
                        . 'which pending csv 30 days need export again'
            ]
        );
        $this->addColumn(
            'sales_order_grid',
            'csv_start_date',
            [
                'type' => Table::TYPE_DATETIME,
                'comment' => 'This field is used for detect orders '
                            . 'which pending csv 30 days need export again'
            ]
        );
    }
}

