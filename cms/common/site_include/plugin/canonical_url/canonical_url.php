<?php

class CanonicalUrlPlugin{

	const PLUGIN_ID = "canonical_url";

	function init(){

		CMSPlugin::addPluginMenu(self::PLUGIN_ID,array(
			"name"=>"カノニカルURL挿入プラグイン",
			"description"=>"テンプレートに</head>タグがある場合はカノニカルURLタグを自動で挿入します。",
			"author"=>"齋藤 毅",
			"url"=>"https://saitodev.co/",
			"mail"=>"info@saitodev.co",
			"version"=>"0.2"
		));

		if(CMSPlugin::activeCheck(self::PLUGIN_ID)){
			CMSPlugin::setEvent('onOutput',self::PLUGIN_ID,array($this,"onOutput"),array("filter"=>"all"));
		}
	}

	function onOutput($arg){
		$html = &$arg["html"];

		//ダイナミック編集では挿入しない
		if(defined("CMS_PREVIEW_MODE") && CMS_PREVIEW_MODE) return $html;

		//URLの末尾が.xmlか.jsonの場合は挿入しない
		if(strpos($_SERVER["REQUEST_URI"], ".xml") || strpos($_SERVER["REQUEST_URI"], ".json")) return $html;

		//RSSでは挿入しない
		if(strpos($html, '<rss version="2.0">') !== false || strpos($html, '<feed xml:lang="ja" xmlns="http://www.w3.org/2005/Atom">') !== false) return null;

		//</head>が無い場合は挿入しない
		if(stripos($html, "</head>") === false) return $html;

		//既にCMSBlogPage.class.phpやCMSPage.class.phpでカノニカルURLを組み立てているので、パラメータなしでURLを呼び出せる
		$canonicalUrl = SOY2Logic::createInstance("logic.site.Page.PageLogic")->buildCanonicalUrl();
		$tag = "<link rel=\"canonical\" href=\"" . $canonicalUrl . "\">";

		return str_ireplace('</head>', $tag."\n".'</head>', $html);
	}

	public static function register(){
		$obj = CMSPlugin::loadPluginConfig(self::PLUGIN_ID);
		if(is_null($obj)){
			$obj = new CanonicalUrlPlugin();
		}
		CMSPlugin::addPlugin(self::PLUGIN_ID,array($obj,"init"));
	}
}

CanonicalUrlPlugin::register();
