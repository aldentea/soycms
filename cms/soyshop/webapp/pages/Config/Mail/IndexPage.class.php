<?php
/**
 * @class Config.Mail.IndexPage
 * @date 2009-07-30T18:00:46+09:00
 * @author SOY2HTMLFactory
 */
class IndexPage extends WebPage{

	function __construct(){
		parent::__construct();

		$this->createAdd("mail_plugin_list", "_common.Plugin.MailPluginListComponent", array(
    		"list" => self::_getMailPluginList()
    	));

	}

	private function _getMailPluginList(){
    	SOYShopPlugin::load("soyshop.order.detail.mail");
    	$mailList = SOYShopPlugin::invoke("soyshop.order.detail.mail", array())->getList();
    	if(!count($mailList)) return array();

    	$list = array();
    	foreach($mailList as $values){
    		if(!is_array($values)) continue;
   			foreach($values as $value){
   				$list[] = $value;
   			}
    	}

    	return $list;
    }
}
