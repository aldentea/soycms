<?php
/*
 * soyshop.site.beforeoutput.php
 * Created: 2010/03/11
 */

class CommonSitemapXmlBeforeOutput extends SOYShopSiteBeforeOutputAction{

	function beforeOutput($page){
		$pageObj = $page->getPageObject();

		//カートページとマイページでは読み込まない
		if(get_class($pageObj) != "SOYShop_Page"){
			return;
		}

		//sitemap.xmlでない場合は読み込まない
		if(!preg_match('/sitemap.xml/', $pageObj->getUri())){
			return;
		}

		//フリーページ以外では読み込まない
		$pageType = $pageObj->getType();
		if($pageType != SOYShop_Page::TYPE_FREE){
			return;
		}

		$pages = $this->getPages();

		if(count($pages) == 0){
			return;
		}

		$url = soyshop_get_site_url(true);

		header("Content-Type: text/xml");

		$html = array();
		$html[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$html[] = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.sitemaps.org/schemas/sitemap-image/1.1\" xmlns:video=\"http://www.sitemaps.org/schemas/sitemap-video/1.1\">";

		foreach($pages as $obj){

			$getUri = $obj->getUri();

			if($getUri==SOYSHOP_TOP_PAGE_MARKER){
				$getUri = "";

			//404の場合はスルー
			}else if($getUri == SOYSHOP_404_PAGE_MARKER){
				continue;
			}

			switch($obj->getType()){
				case SOYShop_Page::TYPE_LIST:
					$value = array();
					$pageObject = $obj->getPageObject();
					switch($pageObject->getType()){
						case SOYShop_ListPage::TYPE_CATEGORY:
							//ディフォルトカテゴリがある場合
							if(!is_null($pageObject->getDefaultCategory())){
								$html[] = "	<url>";
								$html[] = "		<loc>" . $url . $getUri . "/</loc>";
								$html[] = "		<priority>0.8</priority>";
								$html[] = "		<lastmod>" . $this->getDate($obj->getUpdateDate()) . "</lastmod>";
								$html[] = "	</url>";
							}

							$categoryIds = $pageObject->getCategories();
							foreach($categoryIds as $categoryId){
								$category = $this->getCategory($categoryId);
								$html[] = "	<url>";
								if(strlen($getUri) == 0){
									$html[] = "		<loc>" . $url.$category->getAlias() . "/</loc>";
								}else{
									$html[] = "		<loc>" . $url.$getUri."/" . $category->getAlias() . "/</loc>";
								}
								$html[] = "		<priority>0.5</priority>";
								$html[] = "		<lastmod>" . $this->getDate($obj->getUpdateDate()) . "</lastmod>";
								$html[] = "	</url>";
							}
							break;
						case SOYShop_ListPage::TYPE_FIELD:
							$value = array();
							$html[] = "	<url>";
							if(strlen($getUri) == 0){
								$html[] = "		<loc>" . $url . "</loc>";
							}else{
								$html[] = "		<loc>" . $url . $getUri . "/</loc>";
							}
							$html[] = "		<priority>0.5</priority>";
							$html[] = "		<lastmod>" . $this->getDate($obj->getUpdateDate()) . "</lastmod>";
							$html[] = "	</url>";
							break;
						case SOYShop_ListPage::TYPE_CUSTOM:
							$moduleId = $pageObject->getModuleId();
							if(isset($moduleId)){
								//カスタムサーチフィールド
								if(strpos($moduleId, "custom_search_field") === 0){
									SOY2::import("module.plugins.custom_search_field.util.CustomSearchFieldUtil");
									$configs = CustomSearchFieldUtil::getConfig();
									if(!count($configs)) continue;
									foreach($configs as $fieldId => $config){
										if(!isset($config["sitemap"]) || !is_numeric($config["sitemap"])) continue;

										/**
										 * @ToDo 多言語化
										 */
										if(!strlen($config["option"]["jp"])) continue;
										$opts = explode("\n", $config["option"]["jp"]);
										foreach($opts as $opt){
											$opt = trim($opt);
											if(!strlen($opt)) continue;
											$html[] = "	<url>";
											$html[] = "		<loc>" . $url . $getUri . "/" . $fieldId . "/" . $opt . "</loc>";
	 										$html[] = "		<priority>0.5</priority>";
	 										$html[] = "		<lastmod>" . $this->getDate($obj->getUpdateDate()) . "</lastmod>";
	 										$html[] = "	</url>";
										}
									}
								}
							}
							break;
					}
					break;
				case SOYShop_Page::TYPE_DETAIL:
					$value = array();
					$items = $this->getItems($obj->getId());
					foreach($items as $item){
						$html[] = "	<url>";
						if(strlen($getUri) == 0){
							$html[] = "		<loc>" . $url . $item->getAlias() . "</loc>";
						}else{
							$html[] = "		<loc>" . $url . $getUri . "/" . $item->getAlias() . "</loc>";
						}
						$html[] = "		<priority>0.8</priority>";
						$html[] = "		<lastmod>" . $this->getDate($item->getUpdateDate()) . "</lastmod>";
						$html[] = "	</url>";
					}
					break;

				case SOYShop_Page::TYPE_COMPLEX:
				case SOYShop_Page::TYPE_FREE:
				case SOYShop_Page::TYPE_SEARCH:
				default:
					if(strpos($getUri, ".xml") == false){
						if(strpos($getUri, ".html") == false && strlen($getUri) > 1){
							$getUri = $getUri . "/";
						}
						$html[] = "	<url>";
						$html[] = "		<loc>" . $url . $getUri . "</loc>";

						//トップページ
						if(!strlen($getUri)){
							$html[] = "		<priority>1.0</priority>";
						}else{
							$html[] = "		<priority>0.5</priority>";
						}

						$html[] = "		<lastmod>" . $this->getDate($obj->getUpdateDate()) . "</lastmod>";
						$html[] = "	</url>";
					}
					break;
			}

		}

		$html[] = "</urlset>";

		$page->addLabel("sitemap.xml", array(
			//"html" => "",
			"html" => implode("\n", $html),
			"soy2prefix" => SOYSHOP_SITE_PREFIX
		));
	}

	function getDate($time){
		return date("Y", $time) . "-" . date("m", $time) . "-" . date("d", $time) . "T" . date("H", $time) . ":" . date("i", $time) . ":" . date("s", $time) . "+09:00";
	}

	function getPages(){
		$dao = SOY2DAOFactory::create("site.SOYShop_PageDAO");
		try{
			$pages = $dao->get();
		}catch(Exception $e){
			$pages = new SOYShop_Page();
		}
		return $pages;
	}

	private $itemDao;

	function getItems($pageId){
		if(!$this->itemDao){
			$this->itemDao = SOY2DAOFactory::create("shop.SOYShop_ItemDAO");
		}

		try{
			$items = $this->itemDao->getByDetailPageIdIsOpen($pageId);
		}catch(Exception $e){
			$items = array();
		}
		return $items;
	}

	private $categoryDao;

	function getCategory($categoryId){
		if(!$this->categoryDao){
			$this->categoryDao = SOY2DAOFactory::create("shop.SOYShop_CategoryDAO");
		}

		try{
			$category = $this->categoryDao->getById($categoryId);
		}catch(Exception $e){
			$category = new SOYShop_Category();
		}
		return $category;
	}
}

SOYShopPlugin::extension("soyshop.site.beforeoutput", "common_sitemap_xml", "CommonSitemapXmlBeforeOutput");
