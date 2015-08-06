<?php
/**
 * Created by PhpStorm.
 * User: bra
 * Date: 6/08/15
 * Time: 13:50
 */

namespace Xilon\GaufretteAssetsBundle\Twig;


use OpenCloud\ObjectStore\Service;

class GaufretteAssetsExtension extends \Twig_Extension {


    private $openCloudObjectStore;
    private $assetContainer;
    private $container;
    private $environment;
    public function __construct(Service $openCloudObjectStore, $assetContainer,$environment){
        $this->openCloudObjectStore=$openCloudObjectStore;
        $this->assetContainer=$assetContainer;
        $this->container=$openCloudObjectStore->getContainer($assetContainer);
        $this->environment=$environment;
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('cdn_asset', array($this, 'cdnAssetFilter')),
        );
    }

    public function cdnAssetFilter($path){
        if(!$this->isProd()){
            return $path;
        }
        $cdnUri=$this->container->getCdn()->getCdnUri();

        return sprintf("%s/%s",$cdnUri,$path);
    }
    public function isProd(){
        return $this->environment=="prod";
    }
    public function getName()
    {
      return "xilon.gauffrete_assets_extension";
    }


}