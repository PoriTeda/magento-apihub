<?php
// @codingStandardsIgnoreFile
/**
 * Riki Basic Setup
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Setup;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var
     */
    protected $_websiteSetup;
    /**
     * @var
     */
    protected $_orderStatusSetup;
    /**
     * @var \Magento\Framework\App\State
     */
    protected  $_state;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Riki\BasicSetup\Model\CategorySetup
     */
    protected $_categorySetup;
    /**
     * @var \Riki\BasicSetup\Model\AdminUserSetup
     */
    protected $_adminUserSetup;

    /**
     * UpgradeSchema constructor.
     * @param \Riki\BasicSetup\Model\WebsiteSetup $websiteSetup
     * @param \Riki\BasicSetup\Model\OrderStatusSetup $orderStatusSetup
     * @param \Riki\BasicSetup\Model\CategorySetup $categorySetup
     * @param \Riki\BasicSetup\Model\AdminUserSetup $adminUserSetup
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\BasicSetup\Model\WebsiteSetup $websiteSetup,
        \Riki\BasicSetup\Model\OrderStatusSetup $orderStatusSetup,
        \Riki\BasicSetup\Model\CategorySetup $categorySetup,
        \Riki\BasicSetup\Model\AdminUserSetup $adminUserSetup,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Registry $registry
    )
    {
        $this->_orderStatusSetup = $orderStatusSetup;
        $this->_websiteSetup  = $websiteSetup;
        $this->_state = $state;
        $this->_registry = $registry;
        $this->_categorySetup = $categorySetup;
        $this->_adminUserSetup = $adminUserSetup;
    }

    /**
     * Upgrade function
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){
        $this->_registry->register('isSecureArea', true);
        $setup->startSetup();
        //create store view
        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $this->_websiteSetup->setupWebsites();
            $this->_websiteSetup->adjustIncrementId($setup);
        }
        //update order status
        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $table = $setup->getTable('sales_order_status_state');
            $setup->run("TRUNCATE TABLE $table");
            $this->_orderStatusSetup->setupBasic();
        }
        //update order status
        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            $table = $setup->getTable('sales_order_status_state');
            $setup->run("TRUNCATE TABLE $table");
            $this->_orderStatusSetup->setupBasic();
        }

        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $this->_websiteSetup->setupWebsites();
            $this->_websiteSetup->adjustIncrementId($setup);
        }
        //setup category
        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $this->_categorySetup->categorySetup();
        }
        //update category
        if (version_compare($context->getVersion(), '0.0.7') < 0) {
            $this->_categorySetup->categorySetup();
        }
        //update category
        if (version_compare($context->getVersion(), '0.0.8') < 0) {
            $this->_categorySetup->categorySetup();
        }
        //add new order state and status ( Closed )
        if (version_compare($context->getVersion(), '0.0.9') < 0) {
            $this->_orderStatusSetup->addCloseState();
        }
        //add new order state and status ( Closed )
        if (version_compare($context->getVersion(), '0.1.0') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        
        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            //add field last_updated password
            $tableName= $setup->getTable('admin_user');
            $fieldName = 'last_updated';
            $connection = $setup->getConnection();
            if(!$connection->tableColumnExists($tableName,$fieldName))
            {
                $connection->addColumn($tableName, $fieldName, [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    'comment' => 'Last updated of user password',
                ]);
            }
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.1.6') < 0) {
            $this->_orderStatusSetup->addCvsHold();
        }
        //add new order status
        if (version_compare($context->getVersion(), '0.1.7') < 0) {
            $this->_orderStatusSetup->addCVScancelationStatus();
        }
        //add new order status cancel processing
        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            $this->_orderStatusSetup->addProcessingCanceledStatus();
        }
        //add new order status cancel processing
        if (version_compare($context->getVersion(), '0.2.2') < 0) {
            $this->_orderStatusSetup->addCVScancelationRefundStatus();
        }

        if (version_compare($context->getVersion(), '0.2.4') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.2.9') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.3.0') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.1') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.4') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.5') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.6') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.7') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.3.8') < 0) {

            $this->_websiteSetup->removeBIPWebsiteConfig();
        }
        if (version_compare($context->getVersion(), '0.3.9') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.0') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.1') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.2') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.3') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.4') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.5') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.6') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.7') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.4.8') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.4.9') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.5.0') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.5.1') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.5.2') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.5.3') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }

        if (version_compare($context->getVersion(), '0.5.4') < 0) {
            $version = '0.1.0';
            $this->_adminUserSetup->setupData($version,$setup);
        }
        if (version_compare($context->getVersion(), '0.5.6') < 0) {
           //remove mass stock update
            $connection = $setup->getConnection();
            $tableName = 'massstockupdate_profiles';
            if($connection->isTableExists($tableName)){
                $connection->dropTable($tableName);
            }
        }
        //end setup
        $setup->endSetup();
    }


}//end class
