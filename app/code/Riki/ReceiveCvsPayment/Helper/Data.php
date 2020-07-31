<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ReceiveCvsPayment\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    /**
     * Recipient module enabled config path
     */
    const CONFIG_CRON_ACTIVE = 'riki_receivecvspayment/receivecvspayment/enabled';

    /**
     * Recipient order paging
     */
    const CONFIG_ORDER_PAGING = 'riki_receivecvspayment/receivecvspayment/paging';

    /**
     * Recipient cron expression config path
     */
    const CONFIG_CRON_EXPRESSION = 'riki_receivecvspayment/receivecvspayment/expression';

    /**
     * Recipient local folder config path
     */
    const CONFIG_CRON_LOCAL_FOLDER = 'riki_receivecvspayment/receivecvspayment/local_folder';

    /**
     * Recipient enable ftp config path
     */
    const CONFIG_CRON_FTP_ENABLED = 'riki_receivecvspayment/receivecvspayment/ftp_enabled';

    /**
     * Recipient Ftp host config path
     */
    const CONFIG_CRON_FTP_HOST = 'riki_receivecvspayment/receivecvspayment/ftp_host';
    /**
     * Recipient ftp user config path
     */
    const CONFIG_CRON_FTP_USER = 'riki_receivecvspayment/receivecvspayment/ftp_user';
    /**
     * Recipient ftp  pass config path
     */
    const CONFIG_CRON_FTP_PASS = 'riki_receivecvspayment/receivecvspayment/ftp_pass';
    /**
     * Recipient ftp folder config path
     */
    const CONFIG_CRON_FTP_FOLDER = 'riki_receivecvspayment/receivecvspayment/ftp_folder';

    /**
     * Recipient order status config path
     */
    const CONFIG_CRON_ORDER_STATUS = 'riki_receivecvspayment/receivecvspayment/statuslist';
    /**
     * Recipient column index config path
     */
    const CONFIG_CRON_COLUMN_INDEX = 'riki_receivecvspayment/receivecvspayment/column_index';


    /**
     * Get Cron expression value config
     *
     * @return mixed
     */
    public function getCronExpression()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cronExpression = $this->scopeConfig->getValue(self::CONFIG_CRON_EXPRESSION, $storeScope);

        return $cronExpression;
    }

    /**
     * Check whether or not the module output is enabled in Configuration
     *
     * @return bool
     */
    public function isEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnabled = $this->scopeConfig->getValue(self::CONFIG_CRON_ACTIVE, $storeScope);
        return $isEnabled;
    }

    /**
     * Get order paging value config
     *
     * @return mixed
     */
    public function getOrderPaging()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $paging = $this->scopeConfig->getValue(self::CONFIG_ORDER_PAGING, $storeScope);
        return $paging;
    }

    /**
     * @return mixed
     */
    public function getLocalFolder()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $localFolder = $this->scopeConfig->getValue(self::CONFIG_CRON_LOCAL_FOLDER,$storeScope);
        return $localFolder;
    }

    /**
     * @return mixed
     */
    public function getFtpEnabled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $ftpEnabled = $this->scopeConfig->getValue(self::CONFIG_CRON_FTP_ENABLED,$storeScope);
        return $ftpEnabled;
    }

    /**
     * @return mixed
     */
    public function getFtpHost()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $host = $this->scopeConfig->getValue(self::CONFIG_CRON_FTP_HOST,$storeScope);
        return $host;
    }

    /**
     * @return mixed
     */
    public function getFtpUser()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $user = $this->scopeConfig->getValue(self::CONFIG_CRON_FTP_USER,$storeScope);
        return $user;
    }

    /**
     * @return mixed
     */
    public function getFtpPass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $pass = $this->scopeConfig->getValue(self::CONFIG_CRON_FTP_PASS,$storeScope);
        return $pass;
    }

    /**
     * @return mixed
     */
    public function getFtpFolder()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $ftpFolder = $this->scopeConfig->getValue(self::CONFIG_CRON_FTP_FOLDER,$storeScope);
        return $ftpFolder;
    }

    /**
     * @return mixed
     */
    public function getStatusList()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $status = $this->scopeConfig->getValue(self::CONFIG_CRON_ORDER_STATUS,$storeScope);
        if($status){
            return explode(',',$status);
        }else{
            return array();
        }
    }

    /**
     * @return mixed
     */
    public function getColumnIndex()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return intval(3);
    }
}