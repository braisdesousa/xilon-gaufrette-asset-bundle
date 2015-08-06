<?php
namespace Xilon\GaufretteAssetsBundle\Command;


use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\ORM\EntityManager;
use Guzzle\Http\Exception\ServerErrorResponseException;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Service;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DeleteAssetsCommand extends ContainerAwareCommand
{
    protected function configure(){

        $this
            ->setName("bds:assets:delete-all")
            ->setDescription('Delete all Assets');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Service $objectStore */

        $objectStore=$this->getContainer()->get("opencloud.object_store");
        $container=$objectStore->getContainer($this->getContainer()->getParameter("rackespace_container_asset_name"));

        $output->writeln("<info> Deleting Assets</info>");


        $deleted = false;
        $tryes = 0;
        while (((!$deleted) && ($tryes < 5))){
            try {
                $tryes++;
                $container->deleteAllObjects();
                $deleted = true;
            } catch (\Exception $e) {
                if($tryes>=5){
                    $output->writeln(sprintf("<error>Guzzle Returned ServerErrorResponseException - Cancelled Upload </error>"));
                    throw $e;
                }
                $output->writeln(sprintf("<error>Guzzle ServerErrorResponseException We will try again in 5 seconds</error>"));
                sleep(5);
            }
        }
        $output->writeln("<info> All Assets have been Deleted</info>");
    }

}