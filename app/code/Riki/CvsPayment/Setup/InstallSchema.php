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
 * Class InstallSchema
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class InstallSchema
    extends \Riki\Framework\Setup\Version\Schema
    implements \Magento\Framework\Setup\InstallSchemaInterface
{
    /**
     * Version 1.0.0
     *
     * @return void
     */
    public function version100()
    {
        $conn = $this->getConnection($this->getTable('sales_order_status'));

        /**
         * Install order statuses from config
         */
        $data = [];

        $statuses = [
            'pending_cvs_payment' => __('PENDING_CVS'),
        ];

        foreach ($statuses as $code => $info) {
            $select = $conn->select()
                ->from($this->getTable('sales_order_status'))
                ->where('status = ?', $code);
            if ($conn->fetchOne($select)) {
                continue;
            }

            $data[] = ['status' => $code, 'label' => $info];
        }

        if ($data) {
            $conn->insertArray(
                $this->getTable('sales_order_status'),
                ['status', 'label'],
                $data
            );
        }


        /**
         * Install order states from config
         */
        $conn = $this->getConnection($this->getTable('sales_order_status_state'));
        $data = [];
        $data[] = [
            'status' => 'pending_cvs_payment',
            'state' => 'new',
            'is_default' => 1,
            'visible_on_front' => 1
        ];

        foreach ($data as $key => $row) {
            $select = $conn->select()
                ->from($this->getTable('sales_order_status_state'))
                ->where('status = ?', $row['status'])
                ->where('state = ?', $row['state']);
            if ($conn->fetchOne($select)) {
                unset($data[$key]);
            }
        }
        if ($data) {
            $conn->insertArray(
                $this->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
            );
        }

    }
}