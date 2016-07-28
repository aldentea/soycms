<?php
/*
 * soyshop.site.onoutput.php
 * Created: 2010/03/04
 */

class CommonNoticeArrivalOnOutput extends SOYShopSiteOnOutputAction{

	const INSERT_INTO_THE_END_OF_HEAD = 2;
	const INSERT_INTO_THE_BEGINNING_OF_BODY = 1;
	const INSERT_INTO_THE_END_OF_BODY = 0;

	/**
	 * @return string
	 */
	function onOutput($html){

		//登録した時の挙動
		if(isset($_GET["notice"]) && $_GET["notice"] == "successed"){
			$script = "<script>alert(\"入荷通知登録を行いました\");</script>";
			$html .= $html . "\n" . $script;
		}

		return $html;
	}
}

SOYShopPlugin::extension("soyshop.site.onoutput", "common_notice_arrival", "CommonNoticeArrivalOnOutput");
?>