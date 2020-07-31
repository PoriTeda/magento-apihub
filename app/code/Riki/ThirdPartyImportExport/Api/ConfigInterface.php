<?php
namespace Riki\ThirdPartyImportExport\Api;

interface ConfigInterface
{
    const ORDER_IMPORT_SFTP_HOST = 'order_import/sftp/host';
    const ORDER_IMPORT_SFTP_PORT = 'order_import/sftp/port';
    const ORDER_IMPORT_SFTP_USERNAME = 'order_import/sftp/username';
    const ORDER_IMPORT_SFTP_PASSWORD = 'order_import/sftp/password';
    const ORDER_IMPORT_SFTP_REMOTE_PATH = 'order_import/import/path';
    const ORDER_IMPORT_SFTP_REMOTE_FILE_ORDER = 'order_import/import/cvs_riki_order';
    const ORDER_IMPORT_SFTP_REMOTE_FILE_ORDER_DETAIL = 'order_import/import/cvs_riki_order_detail';
    const ORDER_IMPORT_SFTP_REMOTE_FILE_SHIPPING = 'order_import/import/cvs_riki_shipping';
    const ORDER_IMPORT_SFTP_REMOTE_FILE_SHIPPING_DETAIL = 'order_import/import/cvs_riki_shipping_detail';
    const ORDER_IMPORT_CRON_SCHEDULE = 'order_import/scheduler/import';
    const ORDER_IMPORT_EMAIL_RECIPIENTS_REPORT = 'order_import/email/error';
    const ORDER_IMPORT_GENERAL_ANCHOR_DATE  = 'order_import/common/anchor_date';
    const ORDER_IMPORT_GENERAL_X_YEAR = 'order_import/common/x_year';

}