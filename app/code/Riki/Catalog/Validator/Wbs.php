<?php
namespace Riki\Catalog\Validator;

class Wbs extends \Zend_Validate_Abstract implements \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR = 'error';

    /**
     * {@inherit}
     *
     * @var mixed[]
     */
    protected $_messageTemplates = [
        self::ERROR => 'A valid WBS Code must start with AC + 8 digits',
    ];

    /**
     * Wbs constructor.
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
        $pattern = '/^AC-\d{8}$/';
        if (!preg_match($pattern, trim($value))) {
            $this->_error(self::ERROR);
            return false;
        }

        return true;
    }
}