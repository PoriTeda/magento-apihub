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

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\Framework\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data
    extends AbstractSetup
    implements InstallDataInterface, UpgradeDataInterface
{
    /**
     * Install data
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup   setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context context
     *
     * @return void
     */
    public function install(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $this->setConnection($setup->getConnection())->execute($context, $setup);
    }

    /**
     * Upgrade data
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup   setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context context
     *
     * @return void
     */
    public function upgrade(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $this->setConnection($setup->getConnection())->execute($context, $setup);
    }

}