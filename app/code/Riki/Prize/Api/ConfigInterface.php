<?php
namespace Riki\Prize\Api;

interface ConfigInterface
{
    const PRIZE = 'prize';
    const PRIZE_EMAIL_OOS_ENABLE = 'prize/prizeoutofstock/enable';
    const PRIZE_EMAIL_OOS_FROM = 'prize/prizeoutofstock/sender';
    const PRIZE_EMAIL_OOS_TO = 'prize/prizeoutofstock/to';
    const PRIZE_EMAIL_OOS_TEMPLATE = 'prize/prizeoutofstock/email_template';
}