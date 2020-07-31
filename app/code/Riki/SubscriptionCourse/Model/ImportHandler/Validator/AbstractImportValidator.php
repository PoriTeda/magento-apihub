<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Magento\Framework\Validator\AbstractValidator;
use \Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

abstract class AbstractImportValidator extends AbstractValidator implements RowValidatorInterface
{
    /**
     * @var \Riki\CsvOrderMultiple\Model\ImportHandler\Order
     */
    protected $context;

    /**
     * @var \Riki\CsvOrderMultiple\Model\ImportHandler\Validator
     */
    protected $validator;

    /**
     * @param \Riki\CsvOrderMultiple\Model\ImportHandler\Order $context
     * @return $this
     */
    public function init($context)
    {
        $this->context = $context;
        return $this;
    }

    public function setValidator(\Riki\SubscriptionCourse\Model\ImportHandler\Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        return true;
    }
}
