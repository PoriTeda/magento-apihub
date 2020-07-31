<?php
namespace Riki\Subscription\Command;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    protected $objectManager;

    public function __construct()
    {
        parent::__construct();
    }
    
    protected function getObjectManager()
    {
        return $this->objectManager;
    }
    
}