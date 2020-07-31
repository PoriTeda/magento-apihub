<?php
// @codingStandardsIgnoreFile
namespace Riki\Loyalty\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(CollectionFactory $collectionFactory, EavSetupFactory $eavSetupFactory)
    {
        $this->collectionFactory = $collectionFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $setup->startSetup();

        $eavSetup->addAttribute(
            'customer',
            'reward_user_setting',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward user setting'
            ]
        );
        $eavSetup->addAttribute(
            'customer',
            'reward_user_redeem',
            [
                'type' => 'int',
                'visible' => 0,
                'required' => false,
                'visible_on_front' => 1,
                'is_user_defined' => 0,
                'is_system' => 1,
                'is_hidden' => 1,
                'label' => 'Reward user redeem'
            ]
        );
        
        $setup->endSetup();
    }
}
