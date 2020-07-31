<?php

namespace Riki\EmailMarketing\Cron;

class ResendEmailQueueStartConsumer
{
    const CONSUMER_NAME = 'rikiMailResend';

    /**
     * @var \Magento\Framework\Shell
     */
    protected $shell;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $pathPhp;

    /**
     * @var string
     */
    protected $pathBinMagento;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }


    /**
     * Init Setting
     */
    public function initSetting()
    {

        $this->pathPhp = $this->getPathPhp();

        $this->pathBinMagento = BP . '/bin/magento';

    }

    public function execute()
    {

        $this->initSetting();

        $maxMessageConsumer = 10000;

        $aCommands[self::CONSUMER_NAME] = $this->pathBinMagento . ' queue:consumers:start --max-messages=' . $maxMessageConsumer . ' ' . static::CONSUMER_NAME;

        $this->executeConsumer($aCommands, $maxMessageConsumer);
    }

    public function executeConsumer($aListCommand, $maxMessageConsumer)
    {

        foreach ($aListCommand as $keyCommand => $command) {
            try {
                $aResultCommand = $this->shell->execute("ps aux | grep -i " . $keyCommand);

                $this->logger->info('Run: ' . $aResultCommand);

                if (strpos($aResultCommand, $command) === false) {
                    $this->shell->execute($this->pathPhp . ' ' . $this->pathBinMagento . ' queue:consumers:start --max-messages=' . $maxMessageConsumer . ' ' . $keyCommand . ' >> /dev/null &');
                }
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
                $this->logger->critical($e->getTraceAsString());
            }
        }
    }

    public function getPathPhp()
    {
        $aCommands = [
            'whereis php',
            'which php'
        ];
        try {
            foreach ($aCommands as $sCommand) {
                $sPhpPath = $this->shell->execute($sCommand);

                $aPhpPath = explode(" ", $sPhpPath);
                foreach ($aPhpPath as $sPhpPathLine) {
                    if (strpos($sPhpPathLine, "/bin/php") !== false) {
                        $sPhpPath = $sPhpPathLine;
                        break;
                    }
                }
                return $sPhpPath;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

    }
}