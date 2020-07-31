<?php
namespace Riki\AdvancedInventory\Model;

class ReAssignation extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'reassignation';

    protected $_eventObject = 'reassignation';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var
     */
    private $order;

    /**
     * ReAssignation constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Riki\AdvancedInventory\Model\ResourceModel\ReAssignation');
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getOrder()
    {
        if (!$this->order) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection */
            $orderCollection = $this->orderCollectionFactory->create();

            $this->order = $orderCollection->addFieldToFilter('increment_id', $this->getData('order_increment_id'))
                ->setPageSize(1)
                ->getFirstItem();
        }

        return $this->order;
    }
}