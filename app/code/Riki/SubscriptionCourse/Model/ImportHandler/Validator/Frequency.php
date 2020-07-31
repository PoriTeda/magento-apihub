<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class Frequency extends AbstractImportValidator
{

    protected $frequencyCollectionFactory;

    public function __construct(
        \Riki\Subscription\Model\Frequency\ResourceModel\Frequency\CollectionFactory $frequencyCollection
    ) {
        $this->frequencyCollectionFactory = $frequencyCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_frequency']) {
            return false;
        }

        $frequencyIds = json_decode($value['subscription_course_frequency'], true);
        $frequencyIdsNotFound = [];
        if ($frequencyIds) {
            foreach ($frequencyIds as $id) {
                $frequencyCollection = $this->frequencyCollectionFactory->create();
                $frequency = $frequencyCollection->addFieldToFilter('frequency_id', $id)->getItems();
                if (!$frequency) {
                    $frequencyIdsNotFound[] = $id;
                }
            }
        } else {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_JSON_FORMAT
                        ),
                        $value['subscription_course_frequency']
                    )
                ]
            );
            return false;
        }

        if ($frequencyIdsNotFound) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_FREQUENCY_NOT_FOUND
                        ),
                        implode(',', $frequencyIdsNotFound)
                    )
                ]
            );
            return false;
        }
    }
}
