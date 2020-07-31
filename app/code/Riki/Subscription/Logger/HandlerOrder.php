<?php


namespace Riki\Subscription\Logger;


class HandlerOrder extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_create_order.log';

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