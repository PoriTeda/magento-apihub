<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Setup\Version;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

/**
 * Class Schema
 *
 * @category  RIKI
 * @package   Riki\Framework\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Schema
    extends AbstractSetup
    implements UpgradeSchemaInterface, InstallSchemaInterface
{
    /**
     * Install schema
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup   setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context context
     *
     * @return void
     */
    public function install(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $this->setConnection($setup->getConnection())->execute($context, $setup);
    }

    /**
     * Upgrade schema
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup   setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context context
     *
     * @return void
     */
    public function upgrade(
        \Magento\Framework\Setup\SchemaSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $this->setConnection($setup->getConnection())->execute($context, $setup);
    }
}