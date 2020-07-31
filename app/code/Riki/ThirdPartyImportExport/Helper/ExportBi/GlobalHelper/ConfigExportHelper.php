<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class ConfigExportHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /*enable di export*/
    const CONFIG_SE_ENABLE = 'di_data_export_setup/secommon/di_data_export_enable';

    /*sFtp config*/
    const CONFIG_SE_FTP_IP = 'setting_sftp/setup_ftp/ftp_id';
    const CONFIG_SE_FTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const CONFIG_SE_FTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const CONFIG_SE_FTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';

    /*send notification email*/
    const CONFIG_SENDEMAIL_ENABLE = 'di_data_export_setup/seemail/shipmentexport_email_enable';

    /*email template*/
    const CONFIG_EMAIL_TEMPLATE = 'di_data_export_setup/seemail/shipmentexport_email_template';

    /*received email*/
    const CONFIG_EMAIL_ALERT = 'di_data_export_setup/seemail/shipmentexport_email_alert';

    /*default sender email*/
    const GLOBAL_SENDER_EMAIL = 'trans_email/ident_support/email';

    /*default sender email*/
    const GLOBAL_SENDER_NAME = 'trans_email/ident_support/name';


    /**
     * get Config by path
     *
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * Get export module config
     *
     * @return mixed
     */
    public function isEnable()
    {
        return $this->getConfig(self::CONFIG_SE_ENABLE);
    }
    /**
     * Get sFtp host export config
     *
     * @return mixed
     */
    public function getSftpHost()
    {
        return $this->getConfig(self::CONFIG_SE_FTP_IP);

    }

    /**
     * Get sFtp port export config
     * @return mixed
     */
    public function getSftpPort()
    {
        return $this->getConfig(self::CONFIG_SE_FTP_PORT);
    }

    /**
     * Get sFtp user - export config
     *
     * @return mixed
     */
    public function getSftpUser()
    {
        return $this->getConfig(self::CONFIG_SE_FTP_USER);
    }

    /**
     * Get sFtp password - export config
     *
     * @return mixed
     */
    public function getSftpPass()
    {
        return $this->getConfig(self::CONFIG_SE_FTP_PASS);
    }

    public function getEmailEnable()
    {
        return $this->getConfig(self::CONFIG_SENDEMAIL_ENABLE);
    }

    /**
     * Get Received email - export config
     *
     * @return mixed
     */
    public function getEmailAlert()
    {
        return $this->getConfig(self::CONFIG_EMAIL_ALERT);
    }

    /**
     * Get Email Template - export config
     *
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->getConfig(self::CONFIG_EMAIL_TEMPLATE);
    }

    /**
     * Get default sender email
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        return $this->getConfig(self::GLOBAL_SENDER_EMAIL);
    }

    /**
     * Get default sender name
     *
     * @return mixed
     */
    public function getSenderName()
    {
        return $this->getConfig(self::GLOBAL_SENDER_NAME);
    }
}