<?php
// @codingStandardsIgnoreFile
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Riki\EmailMarketing\Model\EmailTemplate;

/**
 * Class UpgradeSchema
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var EmailTemplate
     */
    protected $emailTemplate;

    /**
     * UpgradeSchema constructor.
     * @param EmailTemplate $emailTemplate
     */
    public function __construct(
        EmailTemplate $emailTemplate
    )
    {
        $this->emailTemplate = $emailTemplate;
    }

    /**
     * Upgrading process
     *
     * @param SchemaSetupInterface $setup Setup Object
     * @param ModuleContextInterface $context Context Object
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.0.1') < 0) {
            //Add column isMidnight
            $tableName = $installer->getTable('email_template');
            $fieldName = 'send_midnight';
            if (!$installer->getConnection()->tableColumnExists($tableName, $fieldName)) {
                $installer->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => false,
                        'default' => 0,
                        'comment' => 'Ability to send email on midnight'
                    ]
                );
            }

            //install customer email template
            $this->emailTemplate->setupEmailMarketingCustomer('0.0.1');
            $this->emailTemplate->changeFooterEmailContent();
        }
        //update email template again
        if (version_compare($context->getVersion(), '0.2.6') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.2.6');
        }
        if (version_compare($context->getVersion(), '0.2.7') < 0)
        {
            $this->emailTemplate->setupEmailMarketingCustomer('0.2.7');

            $tableName =  'riki_email_queue';
            if(!$installer->getConnection()->isTableExists($tableName))
            {
                $table = $installer->getConnection()
                    ->newTable($installer->getTable($tableName))
                    ->addColumn(
                        'queue_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        10,
                        ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                        'Queue Id'
                    )
                    ->addColumn(
                        'template_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        10,
                        ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                        'Template ID'
                    )
                    ->addColumn(
                        'variables',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        '64k',
                        [],
                        'Newsletter Text'
                    )
                    ->addColumn(
                        'from_name',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        [],
                        'Newsletter Sender Name'
                    )
                    ->addColumn(
                        'from_email',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        [],
                        'Newsletter Sender Email'
                    )
                    ->addColumn(
                        'send_to',
                        \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        200,
                        [],
                        'Receivers'
                    )
                    ->addColumn(
                        'is_sent',
                        \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                        1,
                        ['unsigned' => true, 'nullable' => true, 'default' => '0'],
                        'Queue Status'
                    )
                    ->setComment('Email Queue');
                $installer->getConnection()->createTable($table);
                //update default send mid night
                $tableName = $installer->getTable('email_template');
                $fieldName = 'send_midnight';
                if($installer->getConnection()->tableColumnExists($tableName,$fieldName))
                {
                    $installer->getConnection()->modifyColumn(
                        $tableName,
                        $fieldName,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                            'nullable' => false,
                            'default' => 1,
                            'comment' => 'Ability to send email on midnight'

                        ]
                    );

                }
                else // does not exist, create new one
                {
                    $installer->getConnection()->addColumn(
                        $tableName,
                        $fieldName,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                            'nullable' => false,
                            'default' => 1,
                            'comment' => 'Ability to send email on midnight'
                        ]
                    );
                }
            }

        }
        //update email template again
        if (version_compare($context->getVersion(), '0.3.8') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.3.8');
        }
        //update email template again
        if (version_compare($context->getVersion(), '0.3.9') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.3.9');
        }

        //update email template raw html
        if (version_compare($context->getVersion(), '0.4.1') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.1');
        }
        //update email template html 
        if (version_compare($context->getVersion(), '0.4.2') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.2');
        }
        //update email template html 
        if (version_compare($context->getVersion(), '0.4.3') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.3');
        }
        //update email template html
        if (version_compare($context->getVersion(), '0.4.4') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.4');
        }
        //update email template html
        if (version_compare($context->getVersion(), '0.4.5') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.5');
        }
        //update email template for shipment import/export
        if (version_compare($context->getVersion(), '0.4.6') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.6');
        }
        if (version_compare($context->getVersion(), '0.4.7') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.7');
        }
        if (version_compare($context->getVersion(), '0.4.8') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.8');
        }
        if (version_compare($context->getVersion(), '0.4.9') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.4.9');
        }
        if (version_compare($context->getVersion(), '0.5.0') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.0');
        }
        //spit product for order confirm
        if (version_compare($context->getVersion(), '0.5.1') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.1');
        }
        //Remove delivery date and purchase amount in Subscription disengagement email
        if (version_compare($context->getVersion(), '0.5.2') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.2');
        }

        //Update Subscription profile payment method error is wrong
        if (version_compare($context->getVersion(), '0.5.3') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.3');
        }
        //Update templates sub cancel
        if (version_compare($context->getVersion(), '0.5.4') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.4');
        }
        //Update templates sub cart reauthor
        if (version_compare($context->getVersion(), '0.5.5') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.5');
        }
        //Update templates email confirm order
        if (version_compare($context->getVersion(), '0.5.6') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.6');
        }
        //Update templates email confirm order
        if (version_compare($context->getVersion(), '0.5.7') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.7');
        }
        //Update templates email cancel subscription order time
        if (version_compare($context->getVersion(), '0.5.8') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.8');
        }
        //Update templates email cancel subscription order time
        if (version_compare($context->getVersion(), '0.5.9') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.5.9');
        }
        //Update templates email sport confirm title
        if (version_compare($context->getVersion(), '0.6.1') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.1');
        }
        //Update templates email business reauthorize fail
        if (version_compare($context->getVersion(), '0.6.2') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.2');
        }
        //Update templates email subscription payment method error business
        if (version_compare($context->getVersion(), '0.6.3') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.3');
        }
        //Update templates email subscription payment method error business
        if (version_compare($context->getVersion(), '0.6.4') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.4');
        }
        //Update templates email subscription payment method error business
        if (version_compare($context->getVersion(), '0.6.5') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.5');
        }
        //Update email stock
        if (version_compare($context->getVersion(), '0.6.6') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.6.6');
        }
        /**
         * add field to config email sent
         */
        if (version_compare($context->getVersion(), '0.6.7') < 0) {
            //update default send mid night
            $tableName = $installer->getTable('email_template');
            $fieldName = 'enable_sent';
            if($installer->getConnection()->isTableExists($tableName))
            {
                $installer->getConnection()->addColumn(
                    $tableName,
                    $fieldName,
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        'nullable' => false,
                        'default' => 1,
                        'comment' => 'Config Email Enable Sent'

                    ]
                );

            }
        }

        if (version_compare($context->getVersion(), '0.7.4') < 0) {
            $this->emailTemplate->installErrorCronEmail();
        }
        /**
         * Install email marketing for subscriptioncourse_profile_disengagement_email_template_to_business
         */
        if (version_compare($context->getVersion(), '0.7.5') < 0) {
            $this->emailTemplate->setupEmailMarketingCustomer('0.7.5');
        }

        /**
         * add index RIKI_EMAIL_QUEUE_IS_SENT for riki_email_queue table
         */
        if (version_compare($context->getVersion(), '0.7.6') < 0) {
            $tableName = $installer->getTable('riki_email_queue');
            $fieldName = 'is_sent';

            $installer->getConnection()->addIndex(
                $tableName,
                $installer->getConnection()->getIndexName($tableName, $fieldName),
                $fieldName
            );
        }

        $installer->endSetup();
    }
}

