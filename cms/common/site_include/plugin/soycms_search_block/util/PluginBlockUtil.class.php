<?php

class PluginBlockUtil {

  public static function getTemplateByPageId($pageId){
    return self::__getTemplateByPageId($pageId);
  }

  private static function __getTemplateByPageId($pageId=null){
    static $template;
    if(is_null($template)){
      $template = "";
      $blog = self::getBlogPageById($pageId);

      //ブログページを取得できた場合
      if(!is_null($blog) && !is_null($blog->getId())){
        $uri = str_replace("/" . $_SERVER["SOYCMS_PAGE_URI"] . "/", "", $_SERVER["PATH_INFO"]);
        //トップページ
        if($uri === (string)$blog->getTopPageUri()){
            $template = $blog->getTopTemplate();
            //アーカイブページ
        }else if(strpos($uri, $blog->getCategoryPageUri()) === 0 || strpos($uri, $blog->getMonthPageUri()) === 0){
            $template = $blog->getArchiveTemplate();
            //記事ごとページ
        }else{
            $template = $blog->getEntryTemplate();
        }
      //ブログページ以外
      }else{
        $template = self::getPageById($pageId)->getTemplate();
      }
    }

    return $template;
  }

  public static function getBlockByPageId($pageId){
    return self::__getBlockByPageId($pageId);
  }

  private static function __getBlockByPageId($pageId){
    static $block;
    if(is_null($block)){
      try{
          $blocks = SOY2DAOFactory::create("cms.BlockDAO")->getByPageId($pageId);
      }catch(Exception $e){
          return null;
      }

      if(!count($blocks)) return null;

      $block = null;
      foreach($blocks as $obj){
          if($obj->getClass() == "PluginBlockComponent"){
              $block = $obj;
          }
      }
    }

    return $block;
  }

  public static function getBlogPageByPageId($pageId){
    return self::getBlogPageById($pageId);
  }

  public static function getLimitByPageId($pageId){
    $template = self::__getTemplateByPageId($pageId);
    if(is_null($template)) return null;

    $block = self::__getBlockByPageId($pageId);
    if(is_null($block)) return null;

    if(preg_match('/(<[^>]*[^\/]block:id=\"' . $block->getSoyId() . '\"[^>]*>)/', $template, $tmp)){
      if(preg_match('/cms:count=\"(.*?)\"/', $tmp[1], $ctmp)){
        if(isset($ctmp[1]) && is_numeric($ctmp[1])) return (int)$ctmp[1];
      }
    }

    return null;
  }

  public static function getLabelIdByPageId($pageId){
    $template = self::__getTemplateByPageId($pageId);
    if(is_null($template)) return null;

    $block = self::__getBlockByPageId($pageId);
    if(is_null($block)) return null;

    if(preg_match('/(<[^>]*[^\/]block:id=\"' . $block->getSoyId() . '\"[^>]*>)/', $template, $tmp)){
      if(preg_match('/cms:label=\"(.*?)\"/', $tmp[1], $ctmp)){
        if(isset($ctmp[1]) && is_numeric($ctmp[1])) return (int)$ctmp[1];
      }
    }

    return null;
  }

  private static function getBlogPageById($pageId){
    static $page;
    if(is_null($page)){
      try{
        $page = SOY2DAOFactory::create("cms.BlogPageDAO")->getById($pageId);
      }catch(Exception $e){
        $page = new BlogPage();
      }
    }
    return $page;
  }

  private function getPageById($pageId){
    static $page;
    if(is_null($page)){
      try{
        $page = SOY2DAOFactory::create("cms.PageDAO")->getById($pageId);
      }catch(Exception $e){
        $page = new Page();
      }
    }
    return $page;
  }
}
