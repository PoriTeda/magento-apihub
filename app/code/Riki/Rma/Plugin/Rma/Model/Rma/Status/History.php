<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma\Status;

class History
{
    const SEPARATOR = '¤';

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface
     */
    protected $historyRepository;

    /**
     * @var \Riki\Rma\Api\GridRepositoryInterface
     */
    protected $gridRepository;

    /**
     * History constructor.
     *
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Api\GridRepositoryInterface $gridRepository
     * @param \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository
     */
    public function __construct(
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Api\GridRepositoryInterface $gridRepository,
        \Riki\Rma\Api\Rma\Status\HistoryRepositoryInterface $historyRepository
    ){
        $this->gridRepository = $gridRepository;
        $this->historyRepository = $historyRepository;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Extend afterSave
     *
     * @param \Magento\Rma\Model\Rma\Status\History $subject
     * @param \Magento\Rma\Model\Rma\Status\History $result
     * @return \Magento\Rma\Model\Rma\Status\History
     */
    public function afterAfterSave(\Magento\Rma\Model\Rma\Status\History $subject, \Magento\Rma\Model\Rma\Status\History $result)
    {
        if (!$result->dataHasChangedFor('comment')) {
            return $result;
        }

        $gridItem = $this->searchHelper
            ->getByEntityId($result->getRmaEntityId())
            ->getOne()
            ->execute($this->gridRepository);

        if (!$gridItem instanceof \Magento\Rma\Model\Grid) {
            return $result;
        }

        $histories = $this->searchHelper
            ->getByRmaEntityId($result->getRmaEntityId())
            ->getByStatus(new \Zend_Db_Expr('NOT NULL'), 'is')
            ->getAll()
            ->execute($this->historyRepository);
        $comment = [];
        foreach ($histories as $history) {
            $comment[] = $history->getData('comment');
        }

        $gridItem->setData('comment', implode(self::SEPARATOR, $comment));
        $this->gridRepository->save($gridItem);

        return $result;
    }
}