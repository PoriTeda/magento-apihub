<?php
// @codingStandardsIgnoreFile
namespace Riki\GoogleTagManager\Setup;

use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetupFactory; /* For Attribute create  */

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
	/**
	 * @var \Riki\ArReconciliation\Setup\SetupHelper
	 */
	protected $_setupHelper;
	/**
	 * @var \Magento\Customer\Setup\CustomerSetupFactory
	 */
	protected $_customerSetupFactory;
	
	/**
	 * UpgradeSchema constructor.
	 * @param \Riki\ArReconciliation\Setup\SetupHelper $setupHelper
	 */
	public function __construct(
		\Riki\ArReconciliation\Setup\SetupHelper $setupHelper,
		
		\Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
	)
	{
		$this->_setupHelper = $setupHelper;
		$this->_customerSetupFactory = $customerSetupFactory;
	}
	
	/**
	 * {@inheritdoc}
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();
		$customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);
		
		if (version_compare($context->getVersion(), '1.0.1') < 0) {
			
			$connection = $this->_setupHelper->getSalesConnection();
			if (!$connection->tableColumnExists('sales_order', 'ga_client_id')) {
				$connection->addColumn('sales_order', 'ga_client_id', [
					'type' => Table::TYPE_TEXT,
					'length' => '255',
					'comment' => 'Value of ga client id  '
				]);
			}
			
			$attributeName = 'ga_client_id';
			$customerSetup->addAttribute('customer', $attributeName, [
				'type' => 'varchar',
				'input' => 'text',
				'visible' => false,
				'required' => false,
				'system' => 0,
				'sort_order' => 109,
				'position' => 109,
				'label' => 'Value of ga client id'
			]);
			$attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $attributeName);
			$attribute->save();
			$customerSetup->cleanCache();
		}
		
		$setup->endSetup();
	}
}
