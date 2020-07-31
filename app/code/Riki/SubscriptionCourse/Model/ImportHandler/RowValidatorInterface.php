<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler;

interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR_VALUE_IS_REQUIRED = 'isRequired';

    const ERROR_INVALID_INTEGER_NUMBER = 'invalidIntegerNumber';

    const ERROR_INVALID_NUMBER = 'invalidNumber';

    const ERROR_INVALID_DATE_FORMAT = 'invalidDateFormat';

    const ERROR_INVALID_JSON_FORMAT = 'invalidJsonFormat';

    const ERROR_EXCEEDED_MAX_LENGTH = 'exceededMaxLength';

    const ERROR_INVALID_ATTRIBUTE_OPTION = 'absentAttributeOption';

    const ERROR_VALUE_IS_POSITIVE_NUMBER = 'isPositiveNumber';

    const ERROR_INVALID_WBS_FORMAT = 'invalidWbsFormat';

    const ERROR_DUPLICATE_UNIQUE_ATTRIBUTE = 'duplicatedUniqueAttribute';

    const ERROR_INVALID_CATEGORY_ID = 'categoryIdNotFound';

    const ERROR_INVALID_COURSE_ID = 'courseIdNotFound';

    const ERROR_INVALID_B2C_MACHINES_ID = 'machineTypeIdNotFound';

    const ERROR_JSON_KEY_NOT_FOUND = 'jsonKeyNotFound';

    const ERROR_MAIN_CATEGORY_NOT_FOUND = 'mainCategoryNotFound';

    const ERROR_MEMBERSHIP_NOT_FOUND = 'membershipNotFound';

    const ERROR_FREQUENCY_NOT_FOUND = 'frequencyNotFound';

    const ERROR_PAYMENT_NOT_FOUND = 'paymentNotFound';

    const ERROR_WEBSITE_NOT_FOUND = 'websiteNotFound';

    const ERROR_INVALID_ATTRIBUTE_TYPE = 'invalidAttributeType';

    /**
     * Value that means all entities (e.g. websites, groups etc.)
     */
    const VALUE_ALL = 'all';

    /**
     * Initialize validator
     *
     * @param  \Magento\CatalogImportExport\Model\Import\Product $context
     * @return $this
     */
    public function init($context);
}
