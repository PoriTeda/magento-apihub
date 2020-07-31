<?php
namespace Riki\BasicSetup\Plugin;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Page\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
/**
 * Class BodyLineUpClass
 * @package Riki\BasicSetup\Plugin
 */
class BodyLineUpClass implements ObserverInterface
{
    CONST LINE_APP_CLASS = 'line-app';
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * BodyLineUpClass constructor.
     * @param Config $config
     */
    public function __construct(
        RequestInterface $request,
        Config $config
    ){
        $this->config = $config;
        $this->request = $request;
    }
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if($this->request->getParam('lineapp'))
        {
            $this->config->addBodyClass(self::LINE_APP_CLASS);
        }
    }
}