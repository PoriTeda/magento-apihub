<?php
namespace Riki\AdvancedInventory\Api;

interface ConfigInterface
{
    const OUT_OF_STOCK_GENERATE_ORDER_CRON_SCHEDULE = 'advancedinventory_outofstock/generate_order/cron_schedule';
    const OUT_OF_STOCK_GENERATE_ORDER_CRON_SCHEDULE_EXECUTE = 'advancedinventory_outofstock/generate_order/cron_schedule_execute';
    const OUT_OF_STOCK_GENERATE_ORDER_CRON_SCHEDULE_DEFAULT = 'advancedinventory_outofstock/generate_order/cron_schedule_default';
    const OUT_OF_STOCK_GENERATE_ORDER_CRON_BATCH_LIMIT = 'advancedinventory_outofstock/generate_order/cron_bath_limit';
    const OUT_OF_STOCK_FREE_GIFT_EMAIL_RECIPIENTS = 'advancedinventory_outofstock/free_gift/email_recipients';
    const OUT_OF_STOCK_FREE_GIFT_EMAIL_TEMPLATE = 'advancedinventory_outofstock/free_gift/email_template';
}