<?php

namespace Riki\CsvOrderMultiple\Model\ImportHandler;

interface RowValidatorInterface extends \Magento\Framework\Validator\ValidatorInterface
{
    const ERROR_INVALID_TYPE = 'invalidType';

    const ERROR_VALUE_IS_REQUIRED = 'isRequired';

    const ERROR_SKU_NOT_FOUND = 'skuNotFound';

    const ERROR_EXCEEDED_MAX_LENGTH = 'exceededMaxLength';

    const ERROR_INVALID_ATTRIBUTE_TYPE = 'invalidAttributeType';

    const ERROR_ABSENT_REQUIRED_ATTRIBUTE = 'absentRequiredAttribute';

    const ERROR_DUPLICATE_UNIQUE_ATTRIBUTE = 'duplicatedUniqueAttribute';

    const ERROR_INVALID_EMAIL = 'invalidEmail';

    const ERROR_INVALID_DATE_FORMAT = 'invalidDateFormat';

    const ERROR_INVALID_PHONE_NUMBER_FORMAT = 'invalidPhoneNumberFormat';

    const ERROR_INVALID_ZIP_CODE_FORMAT = 'invalidZipCodeFormat';

    const ERROR_INVALID_WBS_FORMAT = 'invalidWbsFormat';

    const ERROR_INVALID_ATTRIBUTE_OPTION = 'absentAttributeOption';

    const ERROR_INVALID_GIFT_WRAPPING_CODE = 'invalidGiftWrappingCode';

    const ERROR_INVALID_BUSINESS_CODE = 'invalidBusinessCode';

    const ERROR_INVALID_PAYMENT_METHOD = 'invalidPaymentMethodCode';

    const ERROR_INVALID_WAREHOUSE_CODE = 'invalidWarehouseCode';

    const ERROR_WAREHOUSE_DELIVERY_TYPE = 'invalidWarehouseCodeDeliveryType';

    const ERROR_WAREHOUSE_STOCK_STATUS = 'invalidWarehouseCodeStockStatus';

    const ERROR_SKU_DISABLE = 'skuDisable';

    /**
     * Value that means all entities (e.g. websites, groups etc.)
     */
    const VALUE_ALL = 'all';

    /**
     * Initialize validator
     *
     * @param \Magento\CatalogImportExport\Model\Import\Product $context
     * @return $this
     */
    public function init($context);
}
