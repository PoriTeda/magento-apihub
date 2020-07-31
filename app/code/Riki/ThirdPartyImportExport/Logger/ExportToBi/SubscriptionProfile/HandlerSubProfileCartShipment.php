<?php
namespace Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile;

use Monolog\Logger;

class HandlerSubProfileCartShipment extends \Magento\Framework\Logger\Handler\Base
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
    protected $fileName = '/var/log/bi_export_subscription_shipment_profile_product_cart.log';

    /**
     * @param $name
     */
    public function setDynamicFileLog($name){
        if(!strpos($this->fileName,$name) !== false){
            $this->fileName = str_replace('/var/log/','/var/log/'.$name.'_',$this->fileName);
            $this->url = str_replace('/var/log/','/var/log/'.$name.'_',$this->url);
        }

    }
}