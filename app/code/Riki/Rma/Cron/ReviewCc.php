<?php
namespace Riki\Rma\Cron;

use Riki\Rma\Model\Config\Source\ReviewCc\Status;

class ReviewCc
{

    /** @var \Riki\Rma\Model\ReviewCcManagement  */
    protected $reviewCcManagement;

    /** @var \Riki\Rma\Model\ResourceModel\ReviewCc\CollectionFactory  */
    protected $reviewCcCollectionFactory;

    /**
     * ReviewCc constructor.
     * @param \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement
     * @param \Riki\Rma\Model\ResourceModel\ReviewCc\CollectionFactory $reviewCcCollectionFactory
     */
    public function __construct(
        \Riki\Rma\Model\ReviewCcManagement $reviewCcManagement,
        \Riki\Rma\Model\ResourceModel\ReviewCc\CollectionFactory $reviewCcCollectionFactory
    )
    {
        $this->reviewCcManagement = $reviewCcManagement;
        $this->reviewCcCollectionFactory = $reviewCcCollectionFactory;
    }

    /**
     * @return $this
     */
    public function process()
    {
        /** @var \Riki\Rma\Model\ResourceModel\ReviewCc\Collection $collection */
        $collection = $this->reviewCcCollectionFactory->create();

        $collection->addFieldToFilter('status', Status::STATUS_NEW);

        /** @var \Riki\Rma\Model\ReviewCc $reviewCc */
        foreach ($collection as $reviewCc) {

            /** @var \Monolog\Logger $logger */
            $logger = $this->reviewCcManagement->getLoggerFactory()->create($reviewCc);

            try {
                $this->reviewCcManagement->approve($reviewCc);

            } catch (\Exception $e) {
                $logger->error($e->getMessage());
                $logger->critical($e);
            }
        }
    }
}