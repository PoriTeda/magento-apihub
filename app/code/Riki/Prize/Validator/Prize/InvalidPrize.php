<?php
namespace Riki\Prize\Validator\Prize;

class InvalidPrize extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR = 'error';

    /**
     * {@inheritdoc}
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR => 'This prize is already existed'
    ];

    /**
     * InvalidPrize constructor.
     *
     */
    public function __construct()
    {
        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
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
        return true;
    }
}