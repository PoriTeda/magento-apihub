<?php
namespace Riki\AdvancedInventory\Model\ReAssignation\ImportHandler;

interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR_VALUE_IS_REQUIRED = 'isRequired';

    const ERROR_EXCEEDED_MAX_LENGTH = 'exceededMaxLength';

    const ERROR_INVALID_ATTRIBUTE_OPTION = 'absentAttributeOption';

    const ERROR_DUPLICATE_UNIQUE_ATTRIBUTE = 'duplicatedUniqueAttribute';

    /**
     * Initialize validator
     *
     * @param \Riki\AdvancedInventory\Model\ReAssignation\ImportHandler\ReAssignation $context
     * @return $this
     */
    public function init($context);
}
