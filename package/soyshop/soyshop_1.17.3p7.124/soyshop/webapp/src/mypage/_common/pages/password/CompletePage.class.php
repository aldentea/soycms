<?php

class CompletePage extends MainMyPagePageBase{

    function __construct() {
    	WebPage::__construct();
    	
    	$this->addLink("top_link", array(
    		"link" => soyshop_get_mypage_top_url()
    	));
    }
}
?>