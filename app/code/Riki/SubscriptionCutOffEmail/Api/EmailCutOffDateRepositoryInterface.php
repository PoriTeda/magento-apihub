<?php
namespace Riki\SubscriptionCutOffEmail\Api;



interface EmailCutOffDateRepositoryInterface
{
    /**
     * @param Data\EmailCutOffDateInterface $emailCutOffDate
     * @return mixed
     */
    public function save(\Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface $emailCutOffDate);

}
