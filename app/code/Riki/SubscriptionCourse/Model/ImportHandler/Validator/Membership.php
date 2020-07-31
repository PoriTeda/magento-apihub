<?php

namespace Riki\SubscriptionCourse\Model\ImportHandler\Validator;

use Riki\SubscriptionCourse\Model\ImportHandler\RowValidatorInterface;

class Membership extends AbstractImportValidator
{

    protected $subscriptionMembership;

    public function __construct(
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $subscriptionMembership
    ) {
        $this->subscriptionMembership = $subscriptionMembership;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();

        if (!$value['subscription_course_membership']) {
            return false;
        }

        $subMembershipIds = json_decode($value['subscription_course_membership'], true);
        $membershipIds = [];

        if ($subMembershipIds) {
            $allMembership = $this->subscriptionMembership->getAllOptions();
            foreach ($allMembership as $membership) {
                $membershipIds[] = $membership['value'];
            }
        } else {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_INVALID_JSON_FORMAT
                        ),
                        $value['subscription_course_membership']
                    )
                ]
            );
            return false;
        }

        $notExistIds = array_diff($subMembershipIds, array_values($membershipIds));
        if ($notExistIds) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_MEMBERSHIP_NOT_FOUND
                        ),
                        implode(',', $notExistIds)
                    )
                ]
            );
            return false;
        } else {
            return true;
        }
    }
}
