<?php
namespace Riki\Prize\Validator\Prize;

class InvalidCustomer extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR = 'error';

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Customer constructor.
     */
    public function __construct() {
        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
        $this->_messageTemplates = [
            self::ERROR => __('Customer with consumer id %1 is not existed', '%value%')
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        if ($value instanceof \Riki\Prize\Model\Prize) {
            if (!$value->getCustomer()) {
                $this->_error(self::ERROR, $value->getConsumerDbId());
                return false;
            }
        }

        return true;
    }
}