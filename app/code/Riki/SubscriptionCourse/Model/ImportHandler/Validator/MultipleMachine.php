<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class MultipleMachine extends AbstractImportValidator
{
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * MultipleMachine constructor.
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
     */
    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
    ) {
        $this->courseRepository = $courseRepository;
        $this->machineTypeFactory = $machineTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!(isset($value['multiple_machine']) && $value['multiple_machine'])) {
            return true;
        }

        $typeIds = json_decode($value['multiple_machine'], true);
        $typeIdsNotFound = [];
        $typeIdsFound = [];
        if ($typeIds) {
            $machineCollection = $this->machineTypeFactory->create()->getCollection()
                                        ->addFieldToFilter('type_id', ['in', $typeIds]);
            if (!$machineCollection->getItems()) {
                $typeIdsNotFound = $typeIds;
            }
            /** @var \Riki\MachineApi\Model\B2CMachineSkus $machineType */
            foreach ($machineCollection->getItems() as $machineType) {
                if (in_array($machineType->getId(), $typeIds)) {
                    $typeIdsFound[] = $machineType->getId();
                }
            }
        }
        $typeIdsNotFound = array_diff($typeIds, $typeIdsFound);
        if ($typeIdsNotFound) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_B2C_MACHINES_ID
                        ),
                        implode(',', $typeIdsNotFound)
                    )
                ]
            );
            return false;
        } else {
            return true;
        }
    }
}
