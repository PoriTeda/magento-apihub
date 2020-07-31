<?php
namespace Riki\SubscriptionProfileDisengagement\Plugin\Subscription\Helper\Profile\Controller;

class Delete
{
    /**
     * Do not validate delete product if profile is disengaged
     *
     * @param \Riki\Subscription\Helper\Profile\Controller\Delete $subject
     * @param \Closure $proceed
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return bool
     */
    public function aroundValidateDeleteProductWithProfileStatus(
        \Riki\Subscription\Helper\Profile\Controller\Delete $subject,
        \Closure $proceed,
        \Riki\Subscription\Model\Profile\Profile $profile
    ) {

        if ($profile->isWaitingToDisengaged()) {
            return true;
        }

        return $proceed($profile);
    }
}
