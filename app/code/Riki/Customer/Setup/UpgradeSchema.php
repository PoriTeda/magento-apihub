<?php
// @codingStandardsIgnoreFile
namespace Riki\Customer\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\DB\Ddl\TriggerFactory
     */
    protected $triggerFactory;

    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * UpgradeSchema constructor.
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Magento\Indexer\Model\IndexerFactory $indexerFactory
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $config,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Amqp\Model\Topology $topology
    ) {
        $this->config = $config;
        $this->triggerFactory = $triggerFactory;
        $this->indexerFactory = $indexerFactory;
        $this->topology = $topology;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $table = $setup->getTable('sales_order_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_nickname',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Riki Nick Name']
                );
                $setup->getConnection()->addColumn(
                    $table, 'firstnamekana',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Fristname Kana']
                );
                $setup->getConnection()->addColumn(
                    $table, 'lastnamekana',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'lastname Kana']
                );
            }
            $table = $setup->getTable('quote_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_nickname',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Riki Nick Name']
                );
                $setup->getConnection()->addColumn(
                    $table, 'firstnamekana',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'Fristname Kana']
                );
                $setup->getConnection()->addColumn(
                    $table, 'lastnamekana',
                    ['type' => Table::TYPE_TEXT, 255, 'default' => null, 'comment' => 'lastname Kana']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            $table = $setup->getTable('sales_order_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->run("ALTER TABLE {$table} DROP COLUMN customer_lastnamekana ");
                $setup->run("ALTER TABLE {$table} DROP COLUMN customer_firstnamekana ");
            }
        }
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('consumer_api_log')
            )->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                array('identity' => true, 'nullable' => false, 'primary' => true),
                'Id'
            )->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                array('nullable' => false),
                'API Name'
            )->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Description'
            )->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                10,
                ['nullable' => false],
                'Status'
            )->addColumn(
                'date',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Date'
            )->addColumn(
                'request',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Request'
            )->addColumn(
                'response_data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Response Data'
            )->setComment(
                'Consumer API logs Table'
            );
            $installer->getConnection()->createTable($table);
        }
        if (version_compare($context->getVersion(), '1.0.8') < 0) {
            $table = $setup->getTable('quote');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'customer_riki_ambassador',
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => 0, 'comment' => 'Riki Ambassador']
                );
            }
            $table = $setup->getTable('sales_order');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'customer_riki_ambassador',
                    ['type' => Table::TYPE_INTEGER, 10, 'default' => 0, 'comment' => 'Riki Ambassador']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.1.8') < 0) {
            /**
             * Create table 'enquiry_category'
             */

            $tableName = $installer->getTable('enquiry_category');
            $tableComment = 'Category management for enquiry customer module';
            $columns = array(
                'entity_id' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => null,
                    'options' => array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true),
                    'comment' => 'Category Id',
                ),
                'name' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Category name',
                ),
                'code' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Category code',
                ),
            );
            /**
             *  We can use the parameters above to create our table
             */

            // Table creation
            $table = $installer->getConnection()->newTable($tableName);

            // Columns creation
            foreach($columns AS $name => $values){
                $table->addColumn(
                    $name,
                    $values['type'],
                    $values['size'],
                    $values['options'],
                    $values['comment']
                );
            }
            // Table comment
            $table->setComment($tableComment);

            // Execute SQL to create the table
            $installer->getConnection()->createTable($table);
        }
        if (version_compare($context->getVersion(), '1.1.3') < 0) {
            $table = $setup->getTable('quote_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_type_address',
                    ['type' => Table::TYPE_TEXT, 50, 'default' => '', 'comment' => 'Riki Type Address']
                );
            }
        }
        if (version_compare($context->getVersion(), '1.1.6') < 0) {
            $table = $setup->getTable('quote_address');
            if ($setup->getConnection()->isTableExists($table) == true) {
                $setup->getConnection()->addColumn(
                    $table, 'riki_nickname',
                    ['type' => Table::TYPE_TEXT, 50, 'default' => '', 'comment' => 'Riki Nickname']
                );
            }
        }

        if (version_compare($context->getVersion(), '1.1.9') < 0) {
            /**
             * Create table 'riki_customer_enquiry_header'
             */

            $tableName = $installer->getTable('riki_customer_enquiry_header');
            $tableComment = 'Enquiry Management for enquiry customer module';
            $columns = array(
                'id' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => 11,
                    'options' => array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true),
                    'comment' => 'Enquiry Id',
                ),
                'increment_id' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Enquiry Increment Id',
                ),
                'order_id' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => true, 'default' => ''),
                    'comment' => 'Order Id',
                ),
                'business_user_name' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Business User Name',
                ),
                'customer_id' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => 11,
                    'options' => array('nullable' => false, 'default' => 0),
                    'comment' => 'Customer Id',
                ),
                'enquiry_category_id' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => 11,
                    'options' => array('nullable' => false, 'default' => 0),
                    'comment' => 'Enquiry Category Id',
                ),
                'enquiry_created_datetime' => array(
                    'type' => Table::TYPE_DATETIME,
                    'size' => null,
                    'options' => array('nullable' => false),
                    'comment' => 'Enquiry Created Datetime',
                ),
                'enquiry_updated_datetime' => array(
                    'type' => Table::TYPE_DATETIME,
                    'size' => null,
                    'options' => array('nullable' => false),
                    'comment' => 'Enquiry Updated Datetime',
                ),
                'enquiry_title' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Enquiry Title',
                ),
                'enquiry_text' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => null,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Enquiry Text',
                )
            );
            /**
             *  We can use the parameters above to create our table
             */

            // Table creation
            $table = $installer->getConnection()->newTable($tableName);

            // Columns creation
            foreach($columns AS $name => $values){
                $table->addColumn(
                    $name,
                    $values['type'],
                    $values['size'],
                    $values['options'],
                    $values['comment']
                );
            }
            // Table comment
            $table->setComment($tableComment);

            // Execute SQL to create the table
            $installer->getConnection()->createTable($table);
        }


        if (version_compare($context->getVersion(), '1.4.8') < 0) {
            /**
             * Create table 'riki_shosha_business_code'
             */

            $tableName = $installer->getTable('riki_shosha_business_code');
            $tableComment = 'Manage shosha business code for customer module';
            $columns = array(
                'id' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => 11,
                    'options' => array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true),
                    'comment' => 'Shosha business code id',
                ),
                'shosha_business_code' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Business code',
                ),

                'shosha_code' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company type',
                ),

                'shosha_cmp' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company name',
                ),

                'shosha_cmp_kana' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company name - Kana',
                ),

                'shosha_dept' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company department name',
                ),

                'shosha_dept_kana' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company department name - Kana',
                ),

                'shosha_in_charge' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Name of person in charge',
                ),

                'shosha_in_charge_kana' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Name of person in charge - Kana',
                ),

                'shosha_postcode' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company zipcode',
                ),

                'shosha_address1' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company address 1',
                ),

                'shosha_address2' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company address 2',
                ),

                'shosha_address1_kana' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company address 1 - Kana',
                ),

                'shosha_address2_kana' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company address 2 - Kana',
                ),

                'shosha_phone' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Company phone number',
                ),

                'shosha_first_code' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'First code',
                ),

                'shosha_second_code' => array(
                    'type' => Table::TYPE_TEXT,
                    'size' => 255,
                    'options' => array('nullable' => false, 'default' => ''),
                    'comment' => 'Second code',
                ),

                'shosha_commission' => array(
                    'type' => Table::TYPE_DECIMAL,
                    'size' => '(12,4)',
                    'options' => array('nullable' => false, 'default' => 0),
                    'comment' => 'Commission',
                ),

                'block_orders' => array(
                    'type' => Table::TYPE_INTEGER,
                    'size' => 11,
                    'options' => array('nullable' => false, 'default' => 0),
                    'comment' => 'Hold customers',
                ),

                'updated_at' => array(
                    'type' => Table::TYPE_DATETIME,
                    'size' => null,
                    'options' => array('nullable' => false),
                    'comment' => 'Update date',
                ),

                'created_at' => array(
                    'type' => Table::TYPE_DATETIME,
                    'size' => null,
                    'options' => array('nullable' => false),
                    'comment' => 'Create date',
                ),
            );
            /**
             *  We can use the parameters above to create our table
             */

            // Table creation
            $table = $installer->getConnection()->newTable($tableName);

            // Columns creation
            foreach($columns AS $name => $values){
                $table->addColumn(
                    $name,
                    $values['type'],
                    $values['size'],
                    $values['options'],
                    $values['comment']
                );
            }
            // Table comment
            $table->setComment($tableComment);

            // Execute SQL to create the table
            $installer->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '1.4.9') < 0) {
            $connection = $setup->getConnection();
            $table = 'riki_shosha_business_code';
            $column = 'orm_rowid';

            if ($connection->isTableExists($table) && !$connection->tableColumnExists($table, $column)) {
                $connection->addColumn(
                    $table, $column,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'default' => 0,
                        'nullable' => true,
                        'after' => 'block_orders',
                        'comment' => 'Shosha customer id from old system'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.5.0') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            if($installer->getConnection()->tableColumnExists($tableName, 'shosha_postode'))
            {
                $installer->getConnection()->dropColumn($tableName,'shosha_postode');

                $installer->getConnection()->addColumn(
                    $tableName,
                    'shosha_postcode',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable' => false,
                        'default' => '',
                        'comment' => 'Company zipcode'
                    ]
                );
            }

            if($installer->getConnection()->tableColumnExists($tableName, 'shosha_comission'))
            {
                $installer->getConnection()->dropColumn($tableName,'shosha_comission');

                $installer->getConnection()->addColumn(
                    $tableName,
                    'shosha_commission',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'size' => '(12,4)',
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Commission'
                    ]
                );
            }

            $table = $installer->getTable('sales_shipment');

            if(!$installer->getConnection()->tableColumnExists($table, 'exported_cedyna_flg')){
                $installer->getConnection()->addColumn($table, 'exported_cedyna_flg', [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    'default'   =>  0,
                    'comment' => 'Is exported to Cedyna?',
                ]);
            }
        }

        if (version_compare($context->getVersion(), '1.6.1') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            $isBiExported = 'is_bi_exported';
            $isCedynaExported = 'is_cedyna_exported';

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName, $isBiExported))
            {
                $connection->addColumn(
                    $tableName, $isBiExported,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN, null,
                        'default'   =>  0,
                        'comment' => 'Is exported to Bi?',
                    ]
                );
            }

            if(!$connection->tableColumnExists($tableName, $isCedynaExported))
            {
                $connection->addColumn(
                    $tableName, $isCedynaExported,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN, null,
                        'default'   =>  0,
                        'comment' => 'Is exported to Cedyna?',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.6.5') < 0) {
            $coreTable = $installer->getTable('core_config_data');
            if ($setup->getConnection()->isTableExists($coreTable) == true) {
                $arrConfigLogin = [
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login?URL=',
                        'scope' => 'default',
                        'scope_id' => 0
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_logout_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/logout/',
                        'scope' => 'default',
                        'scope_id' => 0
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_register_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/customer/member_regist.html?URL=',
                        'scope' => 'default',
                        'scope_id' => 0
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login/init/group/Employee/?URL=',
                        'scope' => 'websites',
                        'scope_id' => 2
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login/init/company/login_cnc/?URL=',
                        'scope' => 'websites',
                        'scope_id' => 3
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login/init/company/login_cis/?URL=',
                        'scope' => 'websites',
                        'scope_id' => 4
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login/init/group/Milano/?URL=',
                        'scope' => 'websites',
                        'scope_id' => 5
                    ],
                    [
                        'path'  => 'sso_login_setting/sso_group/url_login_sso',
                        'value' => 'https://stagingec2.nestle.jp/front/app/common/login/init/group/Alegria/?URL=',
                        'scope' => 'websites',
                        'scope_id' => 6
                    ]
                ];

                $arrConfigLogout = [
                    'path'  => 'sso_login_setting/sso_group/url_logout_sso',
                    'value' => 'https://stagingec2.nestle.jp/front/app/common/logout/',
                    'scope' => 'websites'
                ];

                $arrConfigRegister = [
                    'path'  => 'sso_login_setting/sso_group/url_register_sso',
                    'value' => 'https://stagingec2.nestle.jp/front/customer/member_regist.html?URL=',
                    'scope' => 'websites',
                ];

                for ($i = 0; $i < count($arrConfigLogin); $i++) {
                    $this->config->saveConfig(
                        $arrConfigLogin[$i]['path'],
                        $arrConfigLogin[$i]['value'],
                        $arrConfigLogin[$i]['scope'],
                        $arrConfigLogin[$i]['scope_id']
                    );
                }

                for ($acl = 2; $acl <= 6; $acl++) {
                    $this->config->saveConfig(
                        $arrConfigLogout['path'],
                        $arrConfigLogout['value'],
                        $arrConfigLogout['scope'],
                        $acl
                    );
                }

                for ($acr = 2; $acr <= 6; $acr++) {
                    $this->config->saveConfig(
                        $arrConfigRegister['path'],
                        $arrConfigRegister['value'],
                        $arrConfigRegister['scope'],
                        $acr
                    );
                }
            }
        }

        if (version_compare($context->getVersion(), '1.7.3') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            $cedynaCounter = 'cedyna_counter';

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName, $cedynaCounter))
            {
                $connection->addColumn(
                    $tableName, $cedynaCounter,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, [10,0],
                        'default'   =>  0,
                        'comment' => 'Cedyna Monthly Counter',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.7.4') < 0) {
            $coreTable = $installer->getTable('core_config_data');
            if ($setup->getConnection()->isTableExists($coreTable) == true) {
                $path = 'consumer_db_api_url/setting_base_url/setCustomer_domain';
                $value = 'https://stagingec2.nestle.jp/axis2/services/';
                $this->config->saveConfig($path, $value, 'default', 0);
            }
        }

        if (version_compare($context->getVersion(), '1.7.7') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            if($installer->getConnection()->tableColumnExists($tableName, 'shosha_commission'))
            {
                $installer->getConnection()->dropColumn($tableName,'shosha_commission');

                $installer->getConnection()->addColumn(
                    $tableName,
                    'shosha_commission',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '10,2',
                        'default' => 0.00,
                        'comment' => 'Shosha Commission'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.7.9') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            if($installer->getConnection()->tableColumnExists($tableName, 'shosha_commission'))
            {
                $installer->getConnection()->dropColumn($tableName,'shosha_commission');

                $installer->getConnection()->addColumn(
                    $tableName,
                    'shosha_commission',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                        'length' => '10,2',
                        'default' => 0.00,
                        'comment' => 'Shosha Commission'
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.8.9') < 0) {

            $table = $setup->getTable('riki_shosha_business_code');
            if ($installer->getConnection()->isTableExists($table)) {

                $installer->getConnection()->dropIndex(
                    $table,
                    $installer->getConnection()->getIndexName($table, [
                        'shosha_business_code'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE)
                );

                $installer->getConnection()->addIndex(
                    $table,
                    $installer->getConnection()->getIndexName($table, [
                        'shosha_business_code'
                    ], true),
                    [
                        'shosha_business_code'
                    ], \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE);
            }
        }
        /**
         * Update missing cedyna_counter
         *
         */
        if (version_compare($context->getVersion(), '2.0.0') < 0) {

            $tableName = $installer->getTable('riki_shosha_business_code');

            $cedynaCounter = 'cedyna_counter';

            $connection = $setup->getConnection();

            if(!$connection->tableColumnExists($tableName, $cedynaCounter))
            {
                $connection->addColumn(
                    $tableName, $cedynaCounter,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, [10,0],
                        'default'   =>  0,
                        'comment' => 'Cedyna Monthly Counter',
                    ]
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.2') < 0) {

            $tableName = $installer->getTable('customer_entity');

            $connection = $setup->getConnection();

            if($connection->tableColumnExists($tableName, 'consumer_db_id'))
            {
                $connection->addIndex(
                    $tableName, 'CUSTOMER_ENTITY_CONSUMER_DB_ID',
                    ['consumer_db_id']
                );
            }
        }
        if (version_compare($context->getVersion(), '2.0.3') < 0) {

                $this->topology->install();

        }
        if (version_compare($context->getVersion(), '2.0.4') < 0) {
            /**
             *  Install to update config from queue.xml to RabbitMQ server.
             */
            $this->topology->install();

        }

        if (version_compare($context->getVersion(), '2.0.5') < 0) {

            $connection = $setup->getConnection();

            $tableName = $installer->getTable('riki_customer_enquiry_header');

            $connection->addIndex(
                $tableName,
                $connection->getIndexName('customer_enquiry_header_order_idx', ['order_id'], true),
                ['order_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

            $connection->addIndex(
                $tableName,
                $connection->getIndexName('customer_enquiry_header_customer_idx', ['customer_id'], true),
                ['customer_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            );

        }

        if (version_compare($context->getVersion(), '2.0.7') < 0) {
            /**
             *  Install to update config from queue.xml to RabbitMQ server.
             */
            $this->topology->install();

        }

        if (version_compare($context->getVersion(), '2.0.8') < 0) {
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable('customer_entity'),
                    'consumer_data_hash', [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Consumer data hash'
                    ]
                );
        }

        $setup->endSetup();
    }
}