<?php
namespace Riki\MessageQueue\Model;

use Riki\Framework\Helper\Logger\LoggerBuilder;

class TriggerConsumer
{
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

    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var \Magento\Framework\Shell
     */
    protected $shell;

    /**
     * @var array
     */
    protected $consumers = [];

    /**
     * TriggerConsumer constructor.
     * @param \Magento\Framework\Shell $shell
     * @param LoggerBuilder $loggerBuilder
     */
    public function __construct(
        \Magento\Framework\Shell $shell,
        LoggerBuilder $loggerBuilder
    ) {
        $this->loggerBuilder = $loggerBuilder;
        $this->shell = $shell;
        $this->pathBinMagento = BP . '/bin/magento';
    }

    /**
     *
     */
    public function execute()
    {
        foreach ($this->consumers as $consumer => $maxMessageNum) {
            try {
                if (!$this->isStarted($consumer, $maxMessageNum)) {
                    $command = $this->generateCommand($consumer, $maxMessageNum);
                    $this->shell->execute($this->getPathPhp() . ' ' . $command . '  >> /dev/null &');
                }
            } catch (\Exception $e) {
                $this->getLogger()->critical($e);
            }
        }

        $this->consumers = [];
    }

    /**
     * @param $consumerName
     * @param int $maxMessagesNum
     * @return $this
     */
    public function addConsumer($consumerName, $maxMessagesNum = null)
    {
        $this->consumers[$consumerName] = $maxMessagesNum;

        return $this;
    }

    /**
     * @param $consumerName
     * @param $maxMessageNum
     * @return string
     */
    private function generateCommand($consumerName, $maxMessageNum)
    {
        if ($maxMessageNum) {
            return $this->pathBinMagento . ' queue:consumers:start --max-messages=' . $maxMessageNum . ' ' . $consumerName;
        }

        return $this->pathBinMagento . ' queue:consumers:start ' . $consumerName;
    }

    /**
     * @param $consumerName
     * @param $maxMessageNum
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function isStarted($consumerName, $maxMessageNum)
    {
        $command = $this->generateCommand($consumerName, $maxMessageNum);

        if (strpos(
            $this->shell->execute("ps aux | grep -i " . $consumerName),
            $command
        ) !== false) {
            return true;
        }

        return false;
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    private function getPathPhp()
    {
        if (!$this->pathPhp) {
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

                    $this->pathPhp = $sPhpPath;
                }
            } catch (\Exception $e) {
                $this->getLogger()->critical($e->getMessage());
            }
        }

        return $this->pathPhp;
    }

    /**
     * @return \Riki\Framework\Helper\Logger\Monolog
     * @throws \Exception
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = $this->loggerBuilder
                ->setName('Riki_MessageQueue')
                ->setFileName('trigger_consumer' . '.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();
        }

        return $this->logger;
    }
}
