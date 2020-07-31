<?php

namespace Riki\Customer\Plugin\ControllerAction;

class SyncKssCustomerData
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    private $rikiCustomerRepository;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * SyncKssCustomerData constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param \Magento\Framework\App\ActionInterface $subject
     * @return array
     */
    public function beforeExecute(
        \Magento\Framework\App\ActionInterface $subject
    ) {
        if ($this->customerSession->isLoggedIn()
            && $this->customerSession->getHandleEditAccountInformation()
        ) {
            $this->updateCustomerInfo();
        }

        return [];
    }

    /**
     * @return $this
     */
    protected function updateCustomerInfo()
    {
        $this->customerSession->unsHandleEditAccountInformation();

        $customer = $this->customerSession->getCustomer();

        if ($consumerDbId = $customer->getConsumerDbId()) {
            try {
                $customerApiData = $this->rikiCustomerRepository->prepareAllInfoCustomer($consumerDbId);

                if (isset($customerApiData['customer_api']['email'])) {
                    $this->rikiCustomerRepository->createUpdateEcCustomer(
                        $customerApiData,
                        $consumerDbId,
                        null,
                        $this->customerRepository->getById($customer->getId())
                    );
                }
            } catch (\Exception $e) {
                $this->customerSession->setHasMissingInformation(true);
            }
        }

        return $this;
    }
}
