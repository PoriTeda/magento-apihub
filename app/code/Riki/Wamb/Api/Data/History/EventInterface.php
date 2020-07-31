<?php
namespace Riki\Wamb\Api\Data\History;

interface EventInterface
{
    const ORDER_MATCH_APPLY_RULE      = 'order_apply_rule_success';
    const CRON_SET_AMBASSADOR_FAILD   = 'cron_set_customer_to_ambassador_fail';
    const CRON_SET_AMBASSADOR_SUCCESS = 'cron_set_customer_to_ambassador_success';

}