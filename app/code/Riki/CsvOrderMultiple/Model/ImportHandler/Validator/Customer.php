<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

class Customer extends AbstractImportValidator
{
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface  */
    protected $customerRepository;

    /**
     * Customer constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    )
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $result = true;

        $email = $value['email'];

        $isCreate = false;

        try {
            $customer = $this->customerRepository->get($email);

            if (!$customer || !$customer->getId()) {
                $isCreate = true;
            }
        } catch (\Exception $e) {
            $isCreate = true;
        }

        if ($isCreate) {
            $result = $this->validateRequiredFields([
                'birthdate'
            ], $value);
        }

        return $result;
    }
}
