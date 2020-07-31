<?php
namespace Riki\Subscription\Model\Indexer;

class CartRule implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $indexer;

    public function __construct(
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $indexer
    ) {
        $this->indexer = $indexer;
    }

    public function executeFull()
    {
        return;
    }

    public function executeList(array $ids)
    {
        if (!$ids) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not rebuild index for empty cart-rule array')
            );
        }
        try {
            foreach ($ids as $id) {
                $this->indexer->reindexSalesruleAll($id,true);
            }

            //$this->indexer->reindexAll($ids);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
        return $this;
    }

    public function executeRow($id)
    {
        if (!$id) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t rebuild the index for an undefined rule.')
            );
        }
        $this->executeList([$id]);
    }

    public function execute($ids)
    {
        $this->executeList($ids);
    }
}