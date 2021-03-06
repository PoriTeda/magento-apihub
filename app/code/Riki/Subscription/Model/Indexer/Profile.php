<?php
namespace Riki\Subscription\Model\Indexer;

class Profile implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $indexer;

    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $indexer
    ) {
        $this->indexer = $indexer;
    }

    /**
     * Execute full indexation
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    public function executeFull()
    {
        try {
            $this->indexer->reindexAll();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
        return $this;
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    public function executeList(array $ids)
    {
        if (!$ids) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not rebuild index for empty products array')
            );
        }
        try {
            $this->indexer->reindexAll($ids);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
        return $this;
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function executeRow($id)
    {
        if (!$id) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined product.')
            );
        }
        $this->indexer->reindexRow($id);
    }


    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->executeList($ids);
    }
}