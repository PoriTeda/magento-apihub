<?php
/**
 * Email Marketing Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    /**
     * Shipping exporter configuration : enable/disable
     */
    const CONFIG_EMAIL_FOOTER = 'trans_email/emailtemplate/emailfooter';

    const CONFIG_DATA_PATH = '/app/code/Riki/EmailMarketing/Data/';

    const CONFIG_DATA_VERSION_PATH = '/app/code/Riki/EmailMarketing/Data/Version/';

    const CONFIG_DATA_CUSTOMER = 'customerlist';

    const CONFIG_DATA_BUSINESS_USER = 'businessuser.csv';

    const CONFIG_DATA_EMAIL_HANPUKAI_ORDER_CONFIRMATION = 'sales_email/order/hanpukai_order_confirmation';

    const CONFIG_DATA_EMAIL_SPOT_ORDER_CONFIRMATION = 'sales_email/order/template';

    const CONFIG_DATA_EMAIL_SPOT_ORDER_MULTICHECKOUT_CONFIRMATION = 'sales_email/order/template_multicheckout';

    const CONFIG_DATA_EMAIL_SPOT_ORDER_CHANGE = 'sales_email/order/spot_order_change';

    const CONFIG_DATA_EMAIL_SPOT_ORDER_CHANGE_ENABLE = 'sales_email/order/spot_order_change_enable';

    const CONFIG_DATA_EMAIL_SUBSCRIPTION_CHANGE = 'sales_email/order/subscription_order_change';

    const CONFIG_DATA_EMAIL_SUBSCRIPTION_CHANGE_ENABLE = 'sales_email/order/subscription_order_change_enable';

    const CONFIG_DATA_UNSEND_EMAIL_ON_HOURS = 'emailqueue/setting/unsendhours';
    /**
     * @var Csv
     */
    protected $csvReader;
    /**
     * @var Filesystem
     */
    protected $fileSystem;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var TimezoneInterface
     */
    protected $timeZone;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * Data constructor.
     * @param Context $context
     * @param Csv $csvReader
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Context $context,
        Csv $csvReader,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        \Magento\Framework\Stdlib\DateTime\DateTime\Proxy $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface\Proxy $timezone
    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context;
        $this->csvReader = $csvReader;
        $this->fileSystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->dateTime = $dateTime;
        $this->timeZone = $timezone;

    }

    /**
     * @return string
     */
    public function getEmailFooter()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $emailFooter = $this->scopeConfig->getValue(self::CONFIG_EMAIL_FOOTER, $storeScope);
        return $emailFooter;
    }

    /**
     * @param $file
     * @return mixed
     */
    public function getCsvContent($file)
    {
        $baseDir = $this->directoryList->getPath(DirectoryList::ROOT);
        $fileName= $baseDir.$file;
        return $this->csvReader->getData($fileName);
    }

    /**
     * @param $fileName
     * @return mixed
     */
    public function getTxtContent($fileName)
    {
        $reader = $this->fileSystem->getDirectoryRead
        (
            DirectoryList::ROOT
        );
        $filePath = self::CONFIG_DATA_PATH.$fileName;
        return $reader->openFile($filePath)->readAll();
    }

    /**
     * @return mixed
     */
    public function getCustomerEmailList($version)
    {
        $fileName = self::CONFIG_DATA_VERSION_PATH.self::CONFIG_DATA_CUSTOMER.'_'.$version.'.csv';
        return $this->getCsvContent($fileName);
    }

    /**
     * @return mixed
     */
    public function getTempalteEmailOrderConfirmationHanpukai()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $templateId = $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_HANPUKAI_ORDER_CONFIRMATION, $storeScope);
        return $templateId;
    }
    /**
     * @return mixed
     */
    public function getTempalteEmailOrderConfirmationSpot()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $templateId = $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SPOT_ORDER_CONFIRMATION, $storeScope);
        return $templateId;
    }
    /**
     * @return mixed
     */
    public function getTempalteEmailOrderConfirmationSpotMulti()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $templateId = $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SPOT_ORDER_MULTICHECKOUT_CONFIRMATION, $storeScope);
        return $templateId;
    }
    /**
     * @return mixed
     */
    public function getTempalteEmailOrderChangeSpot()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $templateId = $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SPOT_ORDER_CHANGE, $storeScope);
        return $templateId;
    }

    /**
     * @return mixed
     */
    public function isEnableToSendSpotOrderChange()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SPOT_ORDER_CHANGE_ENABLE, $storeScope);
    }
    /**
     * @return mixed
     */
    public function getTempalteEmailOrderChangeSubscription()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $templateId = $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SUBSCRIPTION_CHANGE, $storeScope);
        return $templateId;
    }
    /**
     * @return mixed
     */
    public function isEnableToSendSubscriptionOrderChange()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue(self::CONFIG_DATA_EMAIL_SUBSCRIPTION_CHANGE_ENABLE, $storeScope);
    }

    /**
     * @param $paymentCode
     * @return mixed
     */
    public function getPaymentTitle($paymentCode)
    {
        $configPath = 'payment/'.$paymentCode.'/title';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($configPath, $storeScope);
    }

    /**
     * @return bool
     */
    public function isMidnightHour()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $originDate = $this->timeZone->formatDateTime($this->dateTime->gmtDate(), 2);
        $needHour = $this->dateTime->gmtDate('H', $originDate);
        $avoidHours = $this->scopeConfig->getValue(self::CONFIG_DATA_UNSEND_EMAIL_ON_HOURS, $storeScope);
        $arrHours = array_map('intval', explode(',', $avoidHours));
        if($arrHours && in_array($needHour,$arrHours))
        {
            return true;
        }
        return false;
    }
}