<?php
// tests/AppBundle/Command
namespace Tests\AppBundle\Command;

use AppBundle\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\HelperSet;

use AppBundle\Command\CacheCommand;
//use PHPUnit\Framework\TestCase;

class CacheCommandTest extends KernelTestCase
{
    public function testCache47()
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        
        $application = new Application($kernel);
        $application->add(new CacheCommand());
        
        $command = $application->find('atlasgrove:cache');
        
        $commandTester = new CommandTester($command);
        
         $commandTester->execute(array(
        'command' => $command->getName(),
        'id'=>'47'
        ));
        
        $output = $commandTester->getDisplay();
        $this->assertContains('Cache Shape 47 to ', $output);
    }
    
    
    public function testCacheStatesList()
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        
        $application = new Application($kernel);
        $application->add(new CacheCommand());
        
        $command = $application->find('atlasgrove:cache');
        
        $commandTester = new CommandTester($command);
              
        $commandTester->execute(array(
        'command' => $command->getName(),
        "--states-list" => true
        ));
        
        // $this->assertRegExp('/.../', $commandTester->getDisplay());
        $output = $commandTester->getDisplay();
        $this->assertContains('Cache States List', $output);
    }
    
}