<?php
namespace Riki\Rma\Api\Data\Rma;

interface RefundStatusInterface
{
    const WAITING_APPROVAL = 8;
    const APPROVED = 9;
    const GAC_FEEDBACK_REJECTED_NEED_ADJUSTMENT = 10;
    const GAC_FEEDBACK_REJECTED_NO_NEED_REFUND = 11;
    const GAC_FEEDBACK_REVIEWED_BY_CC = 12;
    const GAC_FEEDBACK_APPROVED_BY_CC = 13;
    const CARD_COMPLETED = 14;
    const SENT_TO_AGENT = 15;
    const BT_COMPLETED = 16;
    const CHANGE_TO_CHECK = 17;
    const CHANGE_TO_BANK = 31;
    const CHECK_ISSUED = 18;
    const MANUALLY_CARD_COMPLETED = 30;
}