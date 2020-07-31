<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Setup;

use Magento\Framework\Setup\ExternalFKSetup;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\CatalogRule\Api\Data\RuleInterface as CatalogRuleInterface;
use Magento\SalesRule\Api\Data\RuleInterface as SalesRuleRuleInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var ExternalFKSetup
     */
    protected $externalFKSetup;

    /**
     * @param MetadataPool $metadataPool
     * @param ExternalFKSetup $externalFKSetup
     */
    public function __construct(
        MetadataPool $metadataPool,
        ExternalFKSetup $externalFKSetup
    ) {
        $this->metadataPool = $metadataPool;
        $this->externalFKSetup = $externalFKSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $metadataCatalogRule = $this->metadataPool->getMetadata(CatalogRuleInterface::class);
        $this->externalFKSetup->install(
            $setup,
            $metadataCatalogRule->getEntityTable(),
            $metadataCatalogRule->getIdentifierField(),
            'magento_banner_catalogrule',
            'rule_id'
        );

        $metadataSalesRule = $this->metadataPool->getMetadata(SalesRuleRuleInterface::class);

        $this->externalFKSetup->install(
            $setup,
            $metadataSalesRule->getEntityTable(),
            $metadataSalesRule->getIdentifierField(),
            'magento_banner_salesrule',
            'rule_id'
        );

        $setup->endSetup();
    }
}
