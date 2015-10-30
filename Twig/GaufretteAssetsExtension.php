<?php
/**
 * Created by PhpStorm.
 * User: bra
 * Date: 6/08/15
 * Time: 13:50
 */

namespace Xilon\GaufretteAssetsBundle\Twig;


use Gaufrette\Filesystem;
use Xilon\GaufretteBundle\Service\GaufretteBaseUrlService;

class GaufretteAssetsExtension extends \Twig_Extension {


    private $filesystem;
    private $environment;
    public function __construct(Filesystem $filesystem,$environment){
        $this->filesystem=$filesystem;
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
        $cdnUri=GaufretteBaseUrlService::getGaufretteBaseUrl($this->filesystem);
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