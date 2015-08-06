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

class AssetsCommand extends ContainerAwareCommand
{
    protected function configure(){

        $this
            ->setName("bds:assets:copy")
            ->setDescription('Copy Assets to Gaufrette');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Service $objectStore */

        $output->writeln("<info> Deleting All Asets</info>");
        $objectStore=$this->getContainer()->get("opencloud.object_store");
        $container=$objectStore->getContainer($this->getContainer()->getParameter("rackespace_container_asset_name"));
        $container->deleteAllObjects();

        $output->writeln("<info> Starting Copy</info>");
        $this->uploadFiles($container,$output);
        $output->writeln("<info> Uploading </info><comment> FONT </comment><info> Files </info>");
        $this->uploadFontFiles($container,"ttf",$output);
        $this->uploadFontFiles($container,"eot",$output);
        $this->uploadFontFiles($container,"otf",$output);
        $this->uploadFontFiles($container,"woff",$output);
        $this->uploadFontFiles($container,"svg",$output);
        $output->writeln("<info> Ending Copy</info>");
    }
    public function uploadFiles(Container $container, OutputInterface $output){
        $finder= new Finder();

        $finder->files()->in($this->getContainer()->getParameter("kernel.root_dir")."/../web/bundles")
            ->notName("*.ttf")
            ->notName("*.eot")
            ->notName("*.otf")
            ->notName("*.woff")
            ->notName("*.svg");
        $x=0;
        $files=[];
        foreach($finder as $file){
            if($x<100){
                $files[]=["name"=>sprintf("bundles/%s",$file->getRelativePathname()), "path"=>$file->getRealPath()];
                $x++;
            }

            if($x>=100) {
                $uploaded = false;
                $tryes = 0;
                while (((!$uploaded) && ($tryes < 5))){
                    try {
                        $tryes++;
                        $container->uploadObjects($files);
                        $uploaded = true;
                    } catch (\Exception $e) {
                        if($tryes>=5){
                            $output->writeln(sprintf("<error>Guzzle Returned ServerErrorResponseException - Cancelled Upload </error>"));
                            throw $e;
                        }
                        $output->writeln(sprintf("<error>Guzzle ServerErrorResponseException We will try again in 5 seconds</error>"));
                        sleep(5);
                    }
                }
                $files=[];
                $output->writeln(sprintf("<comment>100 files uploaded</comment>"));
                $x=0;
            }

        }
        if($x!=0){
            $container->uploadObjects($files);
            $output->writeln(sprintf("<comment>%s files uploaded</comment>",$x));
            $x=0;
        }
    }
    public function uploadFontFiles(Container $container,$fileType,$output)
    {
        $output->writeln(sprintf("<info> Uploading </info><comment> %s </comment><info> Files </info>",$fileType));
        $finder=new Finder();
        $finder->files()->in($this->getContainer()->getParameter("kernel.root_dir")."/../web/bundles")->name(sprintf("*.%s",$fileType));
        $contentType=$this->getContentType($fileType);
        foreach($finder as $file){
            $uploaded = false;
            $tryes = 0;
            while (((!$uploaded) && ($tryes < 5))){
                try {
                    $tryes++;
                    /** @var SplFileInfo $file */
                    $container->uploadObject(sprintf("bundles/%s",$file->getRelativePathname()),file_get_contents($file->getRealPath()),
                        [
                            "Access-Control-Allow-Origin"=>"*",
                            "Content-Type" => $contentType
                        ]);
                    $uploaded = true;
                    $output->writeln(sprintf("<info> Uploaded </info><comment> %s </comment><info> File </info>",$file->getRelativePathname()));
                } catch (\Exception $e) {
                    if($tryes>=5){
                        $output->writeln(sprintf("<error>Guzzle Returned ServerErrorResponseException - Cancelled Upload </error>"));
                        throw $e;
                    }
                    $output->writeln(sprintf("<error>Guzzle ServerErrorResponseException We will try again in 5 seconds</error>"));
                    sleep(5);
                }
            }

        }

    }
    public function getContentType($fileType)
    {
        $return = "";
        switch ($fileType) {
            case "ttf":
                $return = "application/x-font-ttf";
                break;
            case "eot":
                $return = "application/vnd.ms-fontobject";
                break;
            case "otf":
                $return = "font/opentype";
                break;
            case "woff":
                $return = "application/font-woff";
                break;
            case "svg":
                $return = "image/svg+xml";
                break;
        }
        return $return;
    }
}