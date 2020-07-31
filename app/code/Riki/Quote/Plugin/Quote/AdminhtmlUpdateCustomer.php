<?php
namespace Riki\Quote\Plugin\Quote;

class AdminhtmlUpdateCustomer
{
    /** @var \Riki\Customer\Model\CustomerRepository  */
    protected $rikiCustomerRepository;

    /** @var \Psr\Log\LoggerInterface  */
    protected $logger;

    /** @var \Magento\Backend\Model\Session\Quote  */
    protected $session;

    protected $updated;

    /**
     * CopyConsumerData constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Backend\Model\Session\Quote $session
    )
    {
        $this->logger = $logger;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->session = $session;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Customer\Api\Data\CustomerInterface|null $customer
     * @return array
     */
    public function beforeSetCustomer(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Customer\Api\Data\CustomerInterface $customer = null
    ){

        if (
            $customer->getId() &&
            !$quote instanceof \Riki\Subscription\Model\Emulator\Cart &&
            !$quote->getData('is_generate') &&
            !$this->isUpdated($quote->getId(), $customer->getId())
        ) {
            $consumerDBIdAttr = $customer->getCustomAttribute('consumer_db_id');
            if ($consumerDBIdAttr) {
                $consumerId = $consumerDBIdAttr->getValue();

                try {
                    $consumerData = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerId);

                    $this->rikiCustomerRepository->createUpdateEcCustomer($consumerData, $consumerId,null, $customer);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }

        return [$customer];
    }

    /**
     * @param $quoteId
     * @param $customerId
     * @return bool
     */
    protected function isUpdated($quoteId, $customerId){

        $key = $quoteId . '-' . $customerId;

        $flagSession = $this->session->getIsUpdatedDataFromConsumerDb() == $key;

        $this->session->setIsUpdatedDataFromConsumerDb($key);

        $result = $this->updated == $key;

        $this->updated = $key;

        if ($result) {
            return $result;
        }

        return $flagSession;
    }
}
