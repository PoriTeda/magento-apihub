<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2015-07-26T12:09:14+00:00
 * File:          Setup/InstallSchema.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\TwoFactorAuth\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Add custom admin/user fields for TFA token
         */
        $installer->getConnection()->addColumn(
            $installer->getTable('admin_user'),
            'tfa_login_enabled',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => '0',
                'length' => 1,
                'comment' => 'Two-Factor Authentication Token'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('admin_user'),
            'tfa_login_secret',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => 255,
                'comment' => 'Two-Factor Authentication Secret'
            ]
        );
        $installer->getConnection()->addColumn(
            $installer->getTable('admin_user'),
            'tfa_last_token',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable' => true,
                'length' => 10,
                'comment' => 'Two-Factor Authentication Last Token Used'
            ]
        );

        $installer->endSetup();

    }
}
