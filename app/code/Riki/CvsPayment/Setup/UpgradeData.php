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

/**
 * Class UpgradeData
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeData
    extends \Riki\Framework\Setup\Version\Data
    implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * Version 1.0.3
     *
     * @return void
     */
    public function version103()
    {
        $conn = $this->getConnection($this->getTable('sales_order'));
        if (!$conn->isTableExists('sales_order')
            || !$conn->isTableExists('sales_order_grid')
        ) {
            return;
        }

        if (!$conn->tableColumnExists('sales_order', 'csv_start_date')
            || !$conn->tableColumnExists('sales_order_grid', 'csv_start_date')
        ) {
            return;
        }


        $rows = $conn->select()
            ->from($this->getTable('sales_order'))
            ->where('csv_start_date IS NOT NULL')
            ->query()
            ->fetchAll();
        foreach ($rows as $row) {
            $conn->update(
                $this->getTable('sales_order_grid'),
                [
                    'csv_start_date' => $row['csv_start_date']
                ],
                'entity_id = ' . $row['entity_id']
            );
        }
    }
}