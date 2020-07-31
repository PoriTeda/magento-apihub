<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Setup;

use Magento\Staging\Setup\AbstractStagingSetup;

/**
 * Setup class to migration staging update for sales rules.
 *
 * @codeCoverageIgnore
 */
class SalesRuleMigration extends AbstractStagingSetup
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Staging\Model\VersionManagerFactory
     */
    private $versionManagerFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Framework\App\State $state
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
     */
    public function __construct(
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
    ) {
        parent::__construct($updateRepository, $updateFactory);

        $this->ruleFactory = $ruleFactory;
        $this->logger = $logger;
        $this->versionManagerFactory = $versionManagerFactory;
        $this->state = $state;
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     */
    public function migrateRules($setup)
    {
        // Emulate area for rules migration
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateRules'],
            [$setup]
        );
    }

    /**
     * Create staging updates by rules.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     */
    public function updateRules($setup)
    {
        $versionManager = $this->versionManagerFactory->create();
        $salesRuleEntityTable = $setup->getTable('salesrule');

        $select = $setup->getConnection()->select()->from(
            $salesRuleEntityTable,
            ['row_id', 'rule_id', 'name', 'from_date', 'to_date', 'is_active']
        );
        $rules = $setup->getConnection()->fetchAll($select);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $currentVersionId = $versionManager->getCurrentVersion()->getId();

        foreach ($rules as $rule) {
            if ($rule['from_date'] || $rule['to_date']) {
                try {
                    //Create an update
                    /** @var \Magento\SalesRule\Model\Rule $ruleModel */
                    $ruleModel = $this->ruleFactory->create();
                    $ruleModel->load($rule['rule_id']);
                    $ruleModel->unsRowId();

                    // Set is_active = false for original rule entity if start date is in the future
                    if ($rule['from_date'] && $rule['is_active']) {
                        $fromDate = new \DateTime($rule['from_date']);
                        if ($fromDate > $now) {
                            $setup->getConnection()->update(
                                $salesRuleEntityTable,
                                ['is_active' => 0],
                                ['row_id = ?' => $rule['row_id']]
                            );
                        }
                    }

                    // Set current update version
                    $versionManager->setCurrentVersionId(
                        $this->createUpdateForEntity($rule)->getId()
                    );

                    // Save staging update for rule
                    $ruleModel->save();

                    if ($rule['to_date']) {
                        // create an update so that the rule is inactive next day after to_date.
                        $ruleModel->unsRowId();
                        $ruleModel->setIsActive(false);
                        $toDate = new \DateTime($rule['to_date']);
                        $toDate->modify('+1 days');
                        $versionManager->setCurrentVersionId(
                            $this->createDeactivateUpdateForRule($rule, $toDate)->getId()
                        );
                        $ruleModel->setCreatedIn($versionManager->getCurrentVersion()->getId());
                        $ruleModel->save();
                    }
                } catch (\Exception $exception) {
                    $this->logger->critical($exception);
                }
            }
        }

        //restore current version
        $versionManager->setCurrentVersionId($currentVersionId);
    }

    /**
     * @param array $rule
     * @param \DateTime $toDate
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    private function createDeactivateUpdateForRule(array $rule, \DateTime $toDate)
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($rule['name'].' deactivate');

        $update->setStartTime($toDate->format('Y-m-d'));
        $update->setIsCampaign(false);
        $this->updateRepository->save($update);
        return $update;
    }
}
