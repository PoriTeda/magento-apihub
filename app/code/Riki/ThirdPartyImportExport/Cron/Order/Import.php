<?php
namespace Riki\ThirdPartyImportExport\Cron\Order;


class Import
{
    const EMAIL_TEMPLATE_ERROR_REPORT = 'thirdpartyimportexport_order_import_error_email_template';

    protected $_logInfo = '';
    protected $_logError = '';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Order\Config
     */
    protected $_config;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Email
     */
    protected $_email;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Sftp
     */
    protected $_sftp;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    protected $_setup;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csv;

    /**
     * Import constructor.
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Riki\ThirdPartyImportExport\Helper\Order\Config $config
     * @param \Riki\ThirdPartyImportExport\Helper\Email $email
     * @param \Riki\ThirdPartyImportExport\Helper\Sftp $sftp
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\Csv $csv
     */
    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface,
        \Riki\ThirdPartyImportExport\Helper\Order\Config $config,
        \Riki\ThirdPartyImportExport\Helper\Email $email,
        \Riki\ThirdPartyImportExport\Helper\Sftp $sftp,
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\Csv $csv
    ) {
        $this->_logger = $loggerInterface;
        $this->_config = $config;
        $this->_email = $email;
        $this->_sftp = $sftp;
        $this->_fileSystem = $filesystem;
        $this->_setup = $setup;
        $this->_csv = $csv;
    }

    public function execute()
    {
        $sftp = [
            'host' => $this->_config->getSftpHost(),
            'port' => $this->_config->getSftpPort(),
            'username' => $this->_config->getSftpUsername(),
            'password' => $this->_config->getSftpPassword()
        ];
        if (empty($sftp['host'])) {
            return $this;
        }
        $connected = $this->_sftp->connect($sftp['host'], $sftp['port'], $sftp['username'], $sftp['password']);
        if ($connected !== true) {
            $this->handleError($connected);
            return $this;
        }

        $queue = [
            [
                'file' => $this->_config->getImportCvs_riki_order(),
                'table' => 'riki_order',
                'columns' => [
                    'order_no', 'shop_code', 'order_datetime',
                    'customer_code', 'guest_flg', 'last_name',
                    'first_name', 'last_name_kana', 'first_name_kana',
                    'email', 'send_email_no', 'postal_code',
                    'prefecture_code', 'address1', 'address2',
                    'address3', 'address4', 'phone_number',
                    'free_shipping_flag', 'reason_code',
                    'owabijou_type', 'store_code', 'advance_later_flg',
                    'payment_method_no', 'payment_method_type',
                    'payment_method_name', 'payment_commission',
                    'payment_commission_tax_rate',
                    'payment_commission_tax',
                    'payment_commission_tax_type',
                    'used_point', 'bonus_point_amount',
                    'point_sheet_number', 'attach_point_mark',
                    'payment_date', 'payment_limit_date',
                    'payment_status', 'payment_money',
                    'purchasing_customer_type', 'customer_group_code',
                    'data_transport_status', 'order_status',
                    'order_type', 'office_order_flg', 'client_group',
                    'caution', 'message', 'payment_order_id',
                    'credit_date', 'credit_status', 'cvs_code',
                    'payment_recepit_no', 'payment_recepit_url',
                    'digital_cash_type', 'transfer_form_flg',
                    'form_issue_count', 'creditpayment_erasing_flg',
                    'creditpayment_count', 'plan_no', 'plan_type',
                    'order_count', 'mastar_sku_code', 'discount_rate',
                    'giftaway_exchange_flg', 'warning_message',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime',
                    'credit_agency_type', 'acq_id'
                ],
                'required' => [
                    'order_no', 'shop_code', 'order_datetime',
                    'customer_code', 'guest_flg', 'last_name',
                    'first_name', 'last_name_kana', 'first_name_kana',
                    'email', 'send_email_no', 'postal_code',
                    'prefecture_code', 'address1', 'address2',
                    'phone_number', 'free_shipping_flag',
                    'advance_later_flg', 'payment_method_no',
                    'payment_method_type', 'payment_commission',
                    'payment_commission_tax_type', 'payment_status',
                    'data_transport_status', 'order_status', 'order_type',
                    'client_group', 'transfer_form_flg', 'form_issue_count',
                    'creditpayment_erasing_flg', 'giftaway_exchange_flg',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime',
                ],
                'datetime' => [
                    'order_datetime', 'created_datetime', 'updated_datetime'
                ],
                'primary' => [
                    'order_no'
                ]
            ],
            [
                'file' => $this->_config->getImportCvs_riki_order_detail(),
                'table' => 'riki_order_detail',
                'columns' => [
                    'order_no', 'shop_code', 'sku_code',
                    'commodity_code', 'commodity_name',
                    'standard_detail1_name', 'standard_detail2_name',
                    'purchasing_amount', 'attach_amount', 'unit_price',
                    'retail_price', 'retail_tax', 'commodity_tax_rate',
                    'commodity_tax', 'commodity_tax_type',
                    'commodity_kbn', 'campaign_code', 'campaign_name',
                    'campaign_discount_rate',
                    'shipping_free_campaign_flg', 'applied_point_rate',
                    'used_point_limit_rate', 'applied_point_amount',
                    'commodity_length', 'commodity_width',
                    'commodity_high', 'commodity_weight',
                    'sale_organization_code', 'sap_commodity_code',
                    'sap_unit_price', 'distribution_detail_flg',
                    'commission_rate', 'orm_rowid',
                    'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ],
                'required' => [
                    'order_no', 'shop_code', 'sku_code',
                    'commodity_code', 'commodity_name',
                    'purchasing_amount', 'attach_amount',
                    'unit_price', 'retail_price', 'retail_tax',
                    'commodity_tax_type', 'commodity_kbn',
                    'applied_point_rate', 'orm_rowid',
                    'created_user', 'created_datetime', 'updated_user',
                    'updated_datetime'
                ],
                'datetime' => [
                    'created_datetime', 'updated_datetime'
                ],
                'primary' => [
                    'order_no', 'shop_code', 'sku_code'
                ]
            ],
            [
                'file' => $this->_config->getImportCvs_riki_shipping(),
                'table' => 'riki_shipping',
                'columns' => [
                    'shipping_no', 'order_no', 'shop_code',
                    'customer_code', 'address_no', 'address_last_name',
                    'address_first_name', 'address_last_name_kana',
                    'address_first_name_kana', 'postal_code',
                    'prefecture_code', 'address1', 'address2',
                    'address3', 'address4', 'phone_number',
                    'delivery_remark', 'acquired_point', 'delivery_slip_no',
                    'shipping_charge', 'shipping_charge_tax_type',
                    'shipping_charge_tax_rate', 'shipping_charge_tax',
                    'shipping_charge_free_flg', 'delivery_type_no',
                    'delivery_type_name', 'delivery_appointed_date',
                    'delivery_appointed_time_start',
                    'delivery_appointed_time_end',
                    'arrival_date', 'arrival_time_start', 'arrival_time_end',
                    'delivery_date', 'sitadori_kbn', 'sitadori_commodity_type',
                    'fixed_sales_status', 'shipping_status',
                    'shipping_direct_date', 'shipping_date', 'shipping_list_flg',
                    'original_shipping_no', 'giftaway_shipping_flg',
                    'return_item_date', 'return_item_loss_money',
                    'return_item_type', 'sap_ren_flg', 'stock_ren_flg',
                    'ship_ren_flg', 'bill_ren_flg', 'order_modify_flg',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime', 'warehouse_code',
                    'delivery_company_code'
                ],
                'required' => [
                    'shipping_no', 'order_no', 'shop_code',
                    'address_last_name', 'address_first_name',
                    'address_last_name_kana', 'address_first_name_kana',
                    'postal_code', 'prefecture_code', 'address1',
                    'address2', 'shipping_charge', 'shipping_charge_free_flg',
                    'shipping_charge_tax_type', 'delivery_type_no',
                    'fixed_sales_status', 'shipping_status', 'shipping_list_flg',
                    'giftaway_shipping_flg', 'orm_rowid',
                    'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ],
                'datetime' => [
                    'created_datetime', 'updated_datetime'
                ],
                'primary' => [
                    'shipping_no'
                ]
            ],
            [
                'file' => $this->_config->getImportCvs_riki_shipping_detail(),
                'table' => 'riki_shipping_detail',
                'columns' => [
                    'shipping_no', 'shipping_detail_no', 'shop_code',
                    'sku_code', 'unit_price', 'discount_price',
                    'discount_amount', 'retail_price', 'retail_tax',
                    'shipping_charge_target_flg', 'purchasing_amount',
                    'gift_code', 'gift_name', 'gift_price', 'gift_tax_rate',
                    'gift_tax', 'gift_tax_type', 'message_card_price',
                    'message_card_tax_type', 'message_card_tax_rate',
                    'message_card_tax', 'message_card_code',
                    'message_card_name', 'message_card_addresses',
                    'message_card_text', 'message_card_sender', 'attach_type',
                    'distribution_detail_flg', 'sitadori_money',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime'
                ],
                'required' => [
                    'shipping_no', 'shipping_detail_no', 'shop_code',
                    'sku_code', 'shipping_charge_target_flg',
                    'purchasing_amount', 'gift_price', 'gift_tax_type',
                    'orm_rowid', 'created_user', 'created_datetime',
                    'updated_user', 'updated_datetime',
                ],
                'datetime' => [
                    'created_datetime', 'updated_datetime'
                ],
                'primary' => [
                    'shipping_no', 'shipping_detail_no'
                ]
            ]
        ];

        foreach ($queue as $item) {
            $this->import($item);
        }

        if ($this->_logInfo) {
            $this->handleSuccess($this->_logInfo);
        }
        if ($this->_logError) {
            $this->handleError($this->_logError);
        }

        return $this;
    }

    /**
     * Execute import
     *
     * @param array $params
     * @return int
     */
    public function import($params = [])
    {
        if (empty($params['file'])) {
            return 0;
        }
        if (empty($params['table'])) {
            return 0;
        }
        $importPath = rtrim($this->_config->getImportPath(), '/') . '/';
        try {
            $this->_sftp->initBackup($importPath);
            $this->_sftp->cd($importPath);
            $files = $this->_sftp->filter($params['file']);
        } catch (\Exception $e) {
            $this->_logError .= $e->__toString() . "\n";
            return 0;
        }

        foreach ($files as $fileName) {
            $this->_logInfo .= "Starting importing...\n";

            $file = $this->_sftp->read($fileName);

            if (!$file) {
                $this->_logInfo .= sprintf("Abort import. [Unable download file %s to local].\n", $fileName);
                return 0;
            }

            $this->_logInfo .= sprintf("File: %s. Table: %s. \n", $fileName, $params['table']);
            $data = $this->_csv->getData($file);

            // check exist header
            if (!array_diff($data[0], $params['columns'])) {
                unset($data[0]);
            }

            $this->_setup->startSetup();

            $conn = $this->_setup->getConnection();
            $table = $this->_setup->getTable($params['table']);
            $count = 0;
            $fail = 0;
            foreach ($data as $num => $row) {
                $ignore = false;
                foreach ($row as $i => $v) {
                    $col = $i;
                    if (is_numeric($i) && isset($params['columns'][$i])) {
                        $col = $params['columns'][$i];
                    }
                    if (in_array($col, $params['required'])) {
                        if (!strlen($v) || $v == 'NULL') {
                            $this->_logInfo .= sprintf("File: %s \nDeny import [%s].\n Error: %s is required column, NULL or empty given.\n", $fileName, implode(', ', $row), $col);
                            $fail++;
                            $ignore = true;
                        }
                    } else {
                        if (!strlen($v) || $v == 'NULL') {
                            $row[$i] = new \Zend_Db_Expr('NULL');
                        }
                    }
                }
                if ($ignore) {
                    continue;
                }
                $normalizeRow = [];
                foreach ($row as $key => $value) {
                    $normalizeRow[$params['columns'][$key]] = $value;
                }
                try {
                    $conn->insert($table, $normalizeRow);
                    $count++;
                } catch (\Zend_Db_Statement_Exception $e) {
                    $where = [];
                    foreach ($params['primary'] as $primary) {
                        $where[$primary . ' = ?'] = $normalizeRow[$primary];
                    }
                    try {
                        $conn->update($table, $normalizeRow, $where);
                        $count++;
                    } catch (\Exception $e) {
                        $this->_logError .= $e->__toString() . "\n";
                        $fail++;
                    }
                } catch (\Exception $e) {
                    $this->_logError .= $e->__toString() . "\n";
                    $fail++;
                }


            }

            $this->_setup->endSetup();

            $this->_logInfo .= sprintf("End Importing. [%s successes. %s fails].\n", $count, $fail);
            $this->_sftp->backup($importPath . $fileName);

            return $count;
        }
    }


    /**
     * Send error report via email
     *
     * @param \Exception|string $log
     */
    public function sendEmailReport($log)
    {
        $receivers = array_filter(explode(',', $this->_config->getEmailError()), 'trim');
        if (!$receivers) {
            return;
        }

        $this->_email
            ->setTo($receivers)
            ->setBody(self::EMAIL_TEMPLATE_ERROR_REPORT, [
                'log' => $log
            ])
            ->send();
    }

    /**
     * @param $msg
     * @return void
     */
    public function handleError($msg)
    {
        $this->_logger->error((string)$msg);
        $this->sendEmailReport($msg);
    }

    /**
     * @param $msg
     * @return void
     */
    public function handleSuccess($msg)
    {
        $this->_logger->info((string)$msg);
        $this->sendEmailReport($msg);
    }
}
