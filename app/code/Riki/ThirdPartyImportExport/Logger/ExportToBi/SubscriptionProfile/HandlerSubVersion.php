<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile;

use Monolog\Logger;

class HandlerSubVersion extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/bi_export_subscription_profile_version.log';
}