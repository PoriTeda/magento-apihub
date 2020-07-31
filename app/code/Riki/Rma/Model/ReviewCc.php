<?php
namespace Riki\Rma\Model;

use Riki\Rma\Model\ResourceModel\ReviewCc\Item\Collection;
use Riki\Rma\Model\ReviewCc\LogFile;

class ReviewCc extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'rma_review_cc';

    protected $_eventObject = 'review_cc';

    /** @var Config\Source\ReviewCc\Status  */
    protected $statusSource;

    /** @var LogFile  */
    protected $logFile;

    /** @var ResourceModel\ReviewCc\Item\CollectionFactory  */
    protected $itemCollectionFactory;

    /**
     * ReviewCc constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Config\Source\ReviewCc\Status $statusSource
     * @param LogFile $logFile
     * @param ResourceModel\ReviewCc\Item\CollectionFactory $itemCollectionFactory
     * @param RmaManagement $rmaManagement
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\Config\Source\ReviewCc\Status $statusSource,
        \Riki\Rma\Model\ReviewCc\LogFile $logFile,
        \Riki\Rma\Model\ResourceModel\ReviewCc\Item\CollectionFactory $itemCollectionFactory,
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->statusSource = $statusSource;
        $this->logFile = $logFile;
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\ResourceModel\ReviewCc');
    }

    /**
     * Get available statuses for Reviews
     *
     * @return array
     */
    public function getAllStatuses()
    {
        return $this->statusSource->getOptions();
    }

    /**
     * @return Collection
     */
    public function getItemCollection()
    {
        /** @var \Riki\Rma\Model\ResourceModel\ReviewCc\Item\Collection $collection */
        $collection = $this->itemCollectionFactory->create();
        $collection->setReviewCcFilter($this);

        if ($this->getId()) {
            foreach ($collection as $item) {
                $item->setOrder($this);
            }
        }
        return $collection;
    }

    /**
     * @return \Riki\Rma\Model\ReviewCc\LogFile
     */
    public function getLogFile()
    {
        return $this->logFile->setReviewCc($this);
    }
}