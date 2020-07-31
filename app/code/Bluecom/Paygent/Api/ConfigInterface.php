<?php
namespace Bluecom\Paygent\Api;

interface ConfigInterface
{
    const PAYGENT_REAUTHORIZE_CRON_EMAIL_RECIPIENTS_BUSINESS = 'paygent_config/authorisation/receiver';
    const PAYGENT_REAUTHORIZE_CRON_EMAIL_SENDER_BUSINESS = 'paygent_config/authorisation/identity';
    const PAYGENT_REAUTHORIZE_CRON_EMAIL_TEMPLATE_BUSINESS = 'paygent_config/authorisation/template_business';
}