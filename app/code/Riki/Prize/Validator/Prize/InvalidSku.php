<?php
namespace Riki\Prize\Validator\Prize;

class InvalidSku extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR = 'error';

    /**
     * Product constructor.
     *
     */
    public function __construct()
    {
        $this->setTranslator(\Magento\Framework\Validator\AbstractValidator::getDefaultTranslator());
        $this->_messageTemplates = [
            self::ERROR => __('Product %1 is not existed', '%value%')
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
            if (!$value->getProduct()) {
                $this->_error(self::ERROR, $value->getSku());
                return false;
            }
        }

        return true;
    }
}