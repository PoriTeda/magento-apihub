<?php
namespace Riki\CsvOrderMultiple\Model\ImportHandler\Validator;

use Riki\CsvOrderMultiple\Model\ImportHandler\RowValidatorInterface;

class BusinessCode extends AbstractImportValidator
{
    /**
     * @var \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory
     */
    protected $shoshaCollectionFactory;

    /**
     * BusinessCode constructor.
     * @param \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory
     */
    public function __construct(
        \Riki\Customer\Model\ResourceModel\Shosha\CollectionFactory $shoshaCollectionFactory
    )
    {
        $this->shoshaCollectionFactory = $shoshaCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        $businessCode = $value['business_code'];

        if (!empty($businessCode)) {
            /** @var \Riki\Customer\Model\ResourceModel\Shosha\Collection $collection */
            $collection = $this->shoshaCollectionFactory->create();

            $collection->addFieldToFilter('shosha_business_code', $businessCode)
                ->setPageSize(1);

            if (!$collection->getSize()) {
                $this->_addMessages(
                    [
                        sprintf(
                            $this->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_INVALID_BUSINESS_CODE
                            ),
                            $businessCode
                        )
                    ]
                );

                return false;
            }
        }

        return true;
    }
}
