<?php
namespace Riki\SapIntegration\Api;

interface ConfigInterface
{
    const SAP_INTEGRATION = 'sap_integration_config';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_NICOS = 'sap_integration_config/sap_customer_id/nicos';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_NICOS_2 = 'sap_integration_config/sap_customer_id/nicos2';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_JCB = 'sap_integration_config/sap_customer_id/jcb';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_JCB_2 = 'sap_integration_config/sap_customer_id/jcb2';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_YAMATO = 'sap_integration_config/sap_customer_id/yamato';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_ASKUL = 'sap_integration_config/sap_customer_id/askul';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_KINKI = 'sap_integration_config/sap_customer_id/kinki';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_TOKAI = 'sap_integration_config/sap_customer_id/tokai';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_WELLNET = 'sap_integration_config/sap_customer_id/wellnet';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_ITOCHU = 'sap_integration_config/sap_customer_id/itochu';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_CEDYNA = 'sap_integration_config/sap_customer_id/cedyna';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_FUKUJUEN = 'sap_integration_config/sap_customer_id/fukujuen';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_LUPICIA = 'sap_integration_config/sap_customer_id/lupicia';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_POINT_PURCHASE = 'sap_integration_config/sap_customer_id/point_purchase';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_YAMATO_GLOBAL_EXPRESS = 'sap_integration_config/sap_customer_id/yamato_global_express';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_ECOHAI = 'sap_integration_config/sap_customer_id/ecohai';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_SAGAWA = 'sap_integration_config/sap_customer_id/sagawa';
    const SAP_INTEGRATION_SAP_CUSTOMER_ID_NP = 'sap_integration_config/sap_customer_id/np';

    const SAP_INTEGRATION_SFTP_HOST = 'setting_sftp/setup_ftp/ftp_id';
    const SAP_INTEGRATION_SFTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const SAP_INTEGRATION_SFTP_USERNAME = 'setting_sftp/setup_ftp/ftp_user';
    const SAP_INTEGRATION_SFTP_PASSWORD = 'setting_sftp/setup_ftp/ftp_pass';

    const SAP_INTEGRATION_EXPORT_SHIPMENT_ENABLE = 'sap_integration_config/export_shipment/enable';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_LIMIT = 'sap_integration_config/export_shipment/limit';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_BATCH_LIMIT = 'sap_integration_config/export_shipment/batch_limit';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_LOCAL = 'sap_integration_config/export_shipment/local';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_EMAIL_NOTIFICATION = 'sap_integration_config/export_shipment/email_notification';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_REMOTE_DIR = 'sap_integration_config/export_shipment/sftp';
    const SAP_INTEGRATION_EXPORT_SHIPMENT_DEBUG = 'sap_integration_config/export_shipment/debug';

    const SAP_INTEGRATION_EXPORT_RMA_ENABLE = 'sap_integration_config/export_rma/enable';
    const SAP_INTEGRATION_EXPORT_RMA_LIMIT = 'sap_integration_config/export_rma/limit';
    const SAP_INTEGRATION_EXPORT_RMA_BATCH_LIMIT = 'sap_integration_config/export_rma/batch_limit';
    const SAP_INTEGRATION_EXPORT_RMA_LOCAL = 'sap_integration_config/export_rma/local';
    const SAP_INTEGRATION_EXPORT_RMA_EMAIL_NOTIFICATION = 'sap_integration_config/export_rma/email_notification';
    const SAP_INTEGRATION_EXPORT_RMA_REMOTE_DIR = 'sap_integration_config/export_rma/sftp';
    const SAP_INTEGRATION_EXPORT_RMA_DEBUG = 'sap_integration_config/export_rma/debug';

    const SAP_INTEGRATION_ENVIRONMENT_LOGIN = 'sap_integration_config/sap_environment/login';
    const SAP_INTEGRATION_ENVIRONMENT_PASSWORD = 'sap_integration_config/sap_environment/password';
    const SAP_INTEGRATION_ENVIRONMENT_ENDPOINT = 'sap_integration_config/sap_environment/endpoint';
}