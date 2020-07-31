<?php
namespace Riki\AdvancedInventory\Plugin\Sales\Block\Order;

class History
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;
    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * History constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
    ) {
        $this->customerSession = $customerSession;
        $this->searchHelper = $searchHelper;
        $this->outOfStockRepository = $outOfStockRepository;
    }

    /**
     * Exclude out of stock order
     *
     * @param \Riki\Sales\Block\Order\History $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetOrders(\Riki\Sales\Block\Order\History $subject, $result)
    {
        if (!$this->customerSession->getCustomerId()) {
            return $result;
        }
        /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $result */
        if (!$result instanceof \Magento\Sales\Model\ResourceModel\Order\Collection) {
            return $result;
        }

        $invisibleOrderIds = $this->searchHelper
            ->getByCallbackMethod($this->customerSession->getCustomerId(), 'getInvisibleOrderIdsByCustomerId')
            ->execute($this->outOfStockRepository);
        if (!$invisibleOrderIds) {
            return $result;
        }

        $result->addFieldToFilter('entity_id', ['nin' => $invisibleOrderIds]);

        return $result;
    }
}