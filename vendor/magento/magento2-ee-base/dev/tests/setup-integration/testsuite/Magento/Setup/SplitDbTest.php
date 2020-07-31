<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Setup\Console\Command\InstallCommand;
use Magento\TestFramework\Deploy\CliCommand;
use Magento\TestFramework\Deploy\DescribeTable;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\SetupTestCase;

/**
 * The purpose of this test is verifying declarative installation works with different shard.
 */
class SplitDbTest extends SetupTestCase
{
    /**
     * @var CliCommand
     */
    private $cliCommand;

    /**
     * @var DescribeTable
     */
    private $describeTable;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    public function setUp()
    {
        $objectManager= Bootstrap::getObjectManager();
        $this->cliCommand = $objectManager->get(CliCommand::class);
        $this->resourceConnection = $objectManager->get(ResourceConnection::class);
        $this->deploymentConfig = $objectManager->get(DeploymentConfig::class);
        $this->describeTable = $objectManager->get(DescribeTable::class);
    }

    /**
     * @moduleName Magento_TestSetupDeclarationModule9OverrideSplit
     * @moduleName Magento_TestSetupDeclarationModule2
     * @dataProviderFromFile Magento/TestSetupDeclarationModule2/fixture/shards.php
     */
    public function testSplitDbInstallation()
    {
        $this->cliCommand->install(
            ['Magento_ScalableCheckout', 'Magento_ScalableOms', 'Magento_TestSetupDeclarationModule9OverrideSplit']
        );
        $this->cliCommand->splitQuote();
        $this->cliCommand->splitSales();
        $this->cliCommand->install(
            ['Magento_TestSetupDeclarationModule2']
        );
        $this->deploymentConfig->resetData();

        $default = $this->describeTable->describeShard('default');
        $sales = $this->describeTable->describeShard('sales');
        $checkout = $this->describeTable->describeShard('checkout');
        //Check if tables were installed on different shards
        self::assertCount(1, $default);
        self::assertCount(1, $sales);
        self::assertCount(1, $checkout);
        self::assertEquals($this->getData(), array_replace($default, $sales, $checkout));
    }

    /**
     * @moduleName Magento_TestSetupDeclarationModule9OverrideSplit
     * @moduleName Magento_TestSetupDeclarationModule2
     * @dataProviderFromFile Magento/TestSetupDeclarationModule2/fixture/shards.php
     */
    public function testUpgradeWithSplitDb()
    {
        $this->cliCommand->install(
            [
                'Magento_TestSetupDeclarationModule9OverrideSplit',
                'Magento_TestSetupDeclarationModule2',
                'Magento_ScalableCheckout',
                'Magento_ScalableOms'
            ]
        );
        $this->cliCommand->splitQuote();
        $this->cliCommand->splitSales();
        $this->deploymentConfig->resetData();
        $this->cliCommand->upgrade();

        $default = $this->describeTable->describeShard('default');
        $sales = $this->describeTable->describeShard('sales');
        $checkout = $this->describeTable->describeShard('checkout');
        //Check if tables were installed on different shards
        self::assertCount(1, $default);
        self::assertCount(1, $sales);
        self::assertCount(1, $checkout);
        self::assertEquals(array_replace($default, $sales, $checkout), $this->getData());
    }

    /**
     * @moduleName Magento_TestSetupDeclarationModule9OverrideSplit
     * @moduleName Magento_TestSetupDeclarationModule2
     */
    public function testBICColumns()
    {
        $this->cliCommand->install(
            [
                'Magento_TestSetupDeclarationModule9OverrideSplit',
                'Magento_TestSetupDeclarationModule2',
                'Magento_ScalableCheckout',
                'Magento_ScalableOms'
            ]
        );
        $this->cliCommand->splitQuote();
        $this->cliCommand->splitSales();
        $this->deploymentConfig->resetData();
        $connection = $this->resourceConnection->getConnection();
        $connection->addColumn(
            $connection->getTableName('auto_increment_test'),
            'some_new_column',
            [
                'type' => 'text',
                'comment' => 'Empty comment'
            ]
        );
        $this->cliCommand->upgrade();
        $checkout = $this->describeTable->describeShard('checkout');
        $default = $this->describeTable->describeShard('default');
        self::assertCount(1, $checkout);
        self::assertCount(1, $default);
        self::assertRegExp('/some_new_column/', reset($checkout));
        self::assertRegExp('/Empty\scomment/', reset($checkout));
    }

    /**
     * @moduleName Magento_TestSetupDeclarationModule9OverrideSplit
     * @moduleName Magento_TestSetupDeclarationModule2
     */
    public function testTableRecreationWithData()
    {
        $this->cliCommand->install(
            [
                'Magento_TestSetupDeclarationModule9OverrideSplit',
                'Magento_TestSetupDeclarationModule2',
                'Magento_ScalableCheckout',
                'Magento_ScalableOms'
            ]
        );
        $this->cliCommand->splitQuote();
        $this->cliCommand->splitSales();
        $this->deploymentConfig->resetData();
        $connection = $this->resourceConnection->getConnection();
        $connection->insert(
            $connection->getTableName('auto_increment_test'),
            ['int_disabled_auto_increment' => 10]
        );
        $this->cliCommand->upgrade();
        $checkoutConnection = $this->resourceConnection->getConnection('checkout');
        $select = $checkoutConnection->select()
            ->from(
                $connection->getTableName('auto_increment_test'),
                ['int_disabled_auto_increment']
            );
        self::assertEquals(
            10,
            $checkoutConnection->fetchOne($select)
        );
    }
}
