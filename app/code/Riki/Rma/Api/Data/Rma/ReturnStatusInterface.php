<?php
namespace Riki\Rma\Api\Data\Rma;

interface ReturnStatusInterface
{
    const CREATED = 1;
    const REJECTED_BY_CC = 2;//CC operator
    const REVIEWED_BY_CC = 3;
    const CC_FEEDBACK_REJECTED = 4; //CC supervisor
    const APPROVED_BY_CC = 5;
    const CS_FEEDBACK_REJECTED = 6;//CS operator or CS supervisor
    const COMPLETED = 7;
    const CLOSED = 19;
}