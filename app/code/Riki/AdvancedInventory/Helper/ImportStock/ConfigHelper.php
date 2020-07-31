<?php

namespace Riki\AdvancedInventory\Helper\ImportStock;

class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /*sFtp config*/
    const CONFIG_SE_FTP_IP = 'setting_sftp/setup_ftp/ftp_id';
    const CONFIG_SE_FTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const CONFIG_SE_FTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const CONFIG_SE_FTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';

    /*warehouse*/
    protected $warehouse = '';

    protected $warehouseList = ['1st', '2nd', '3rd', '4th', '5th'];

    /**
     * @param string $wh
     */
    public function setWarehouse($wh)
    {
        if (in_array($wh, $this->warehouseList)) {
            $this->warehouse = $wh;
        }
    }

    /**
     * get current warehouse
     *  one of warehouse list
     *
     * @return string
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

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

    /**
     * import stock is enable for current warehouse
     *
     * @return mixed
     */
    public function isEnableImportStock()
    {
        $path = 'importstock/common/'.$this->warehouse.'_wh_enable';
        return $this->getConfig($path);
    }

    /**
     * get warehouse id
     *
     * @return mixed
     */
    public function getWarehouseId()
    {
        $path = 'importstock/common/'.$this->warehouse.'_wh';
        return $this->getConfig($path);
    }

    /**
     * Get warehouse location
     *
     * @return mixed
     */
    public function getWarehouseLocation()
    {
        $path = 'importstock/location/'.$this->warehouse.'_wh_location';
        return $this->getConfig($path);
    }

    /**
     * get warehouse pattern
     *
     * @return mixed
     */
    public function getWarehousePattern()
    {
        $path = 'importstock/pattern/'.$this->warehouse.'_wh_pattern';
        return $this->getConfig($path);
    }
}