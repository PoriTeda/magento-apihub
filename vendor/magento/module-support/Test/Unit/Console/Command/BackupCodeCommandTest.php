<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Console\Command;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class BackupCodeCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Helper\Shell|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shellHelper;

    /**
     * @var \Magento\Support\Model\Backup\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupConfig;

    /**
     * @var \Magento\Support\Console\Command\BackupCodeCommand
     */
    protected $model;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->shellHelper = $this->getMockBuilder(\Magento\Support\Helper\Shell::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backupConfig = $this->getMockBuilder(\Magento\Support\Model\Backup\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            \Magento\Support\Console\Command\BackupCodeCommand::class,
            [
                'shellHelper' => $this->shellHelper,
                'backupConfig' => $this->backupConfig,
                'outputPath' => 'var/output/path',
                'backupName' => 'backup_name'
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $backupCommand = 'nice -n 15 tar -czhf var/output/path/backup_name.tar.gz app bin composer.'
            . '* dev *.php lib pub/*.php pub/errors setup update vendor';
        $inputInterface = $this->getMockBuilder(\Symfony\Component\Console\Input\InputInterface::class)
            ->getMockForAbstractClass();
        $outputInterface = $this->getMockBuilder(\Symfony\Component\Console\Output\OutputInterface::class)
            ->getMockForAbstractClass();
        $this->shellHelper->expects($this->any())->method('setRootWorkingDirectory');
        $this->shellHelper->expects($this->any())->method('getUtility')->willReturnMap([
            ['nice', 'nice'],
            ['tar', 'tar']
        ]);
        $this->shellHelper->expects($this->atLeastOnce())->method('execute')->with($backupCommand)
            ->willReturn($backupCommand);
        $this->backupConfig->expects($this->any())->method('getBackupFileExtension')->with('code')
            ->willReturn('tar.gz');
        $outputInterface->expects($this->at(0))->method('writeln')->with($backupCommand);
        $outputInterface->expects($this->at(1))->method('writeln')->with($backupCommand);
        $outputInterface->expects($this->at(2))->method('writeln')->with('Code dump was created successfully');

        $this->model->run($inputInterface, $outputInterface);
    }
}
