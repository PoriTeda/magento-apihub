<?php
namespace Riki\SubscriptionCourse\Command;
use Symfony\Component\Console\Command\Command;

class AbstractCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }
}