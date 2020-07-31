<?php
namespace Riki\SubscriptionCutOffEmail\Model;
use Riki\SubscriptionCutOffEmail\Api\EmailCutOffDateRepositoryInterface;


class EmailCutOffDateRepository implements EmailCutOffDateRepositoryInterface
{

    /**
     * @var ResourceModel\EmailCutOffDate
     */
    protected $resource;

    /**
     * EmailCutOffDateRepository constructor.
     * @param ResourceModel\EmailCutOffDate $emailCutOffDate
     */
    public function __construct(
        \Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate $emailCutOffDate
    )
    {
        $this->resource = $emailCutOffDate;
    }

    public function save(\Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface $emailCutOffDate)
    {

        $this->resource->save($emailCutOffDate);

    }


}