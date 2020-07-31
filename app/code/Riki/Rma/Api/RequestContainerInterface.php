<?php
namespace Riki\Rma\Api;

interface RequestContainerInterface
{
    // const for value of key
    const STARTED = 'started';
    const PROCESSING = 'processing';
    const STOPPED = 'stopped';

    // const for key to control flow
    const ACTION_REFUND_EXPORT_CSV = 'action_refund_export_csv';

}