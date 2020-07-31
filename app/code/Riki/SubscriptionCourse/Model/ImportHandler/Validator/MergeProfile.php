<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class MergeProfile extends AbstractImportValidator
{
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * MergeProfile constructor.
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     */
    public function __construct(
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
    ) {
        $this->courseRepository = $courseRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_merge_profile']) {
            return true;
        }

        $courseIds = json_decode($value['subscription_course_merge_profile'], true);
        $courseIdsNotFound = [];
        if ($courseIds) {
            foreach ($courseIds as $id) {
                try {
                    $course = $this->courseRepository->get($id);
                    //Reject itself
                    if ($course->getCode() == $value['course_code']) {
                        $courseIdsNotFound[] = $id;
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    $courseIdsNotFound[] = $id;
                }
            }
        } else {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_JSON_FORMAT
                        ),
                        $value['subscription_course_merge_profile']
                    )
                ]
            );
            return false;
        }

        if ($courseIdsNotFound) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_COURSE_ID
                        ),
                        implode(',', $courseIdsNotFound)
                    )
                ]
            );
            return false;
        } else {
            return true;
        }
    }
}
