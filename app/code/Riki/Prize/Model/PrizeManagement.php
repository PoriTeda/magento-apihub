<?php
namespace Riki\Prize\Model;

class PrizeManagement implements \Riki\Prize\Api\PrizeManagementInterface
{
    /**
     * @var \Riki\Prize\Api\PrizeRepositoryInterface
     */
    protected $prizeRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * PrizeManagement constructor.
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Prize\Api\PrizeRepositoryInterface $prizeRepository
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Prize\Api\PrizeRepositoryInterface $prizeRepository
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->searchHelper = $searchHelper;
        $this->prizeRepository = $prizeRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @param $cartId
     *
     * @return \Riki\Prize\Api\Data\PrizeInterface[]
     */
    public function getPrizeForCart($cartId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create()->load($cartId);
        if (!$quote->getId()) {
            return [];
        }

        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $quote->getCustomer();
        $consumerAttr = $customer->getCustomAttribute('consumer_db_id');
        if (!$consumerAttr) {
            return [];
        }

        return $this->searchHelper
            ->getByConsumerDbId($consumerAttr->getValue())
            ->getByStatus(0)
            ->getAll()
            ->execute($this->prizeRepository);
    }

}