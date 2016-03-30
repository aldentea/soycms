<?php
/**
 * @class Site.Template.EditorPage
 * @date 2009-11-27T03:36:27+09:00
 * @author SOY2HTMLFactory
 */
class EditorPage extends WebPage{
	
	private $moduleId;
	private $modulePath;
	private $iniPath;

	function doPost(){

		if(soy2_check_token()){
			$config = $_POST["config"];
			
			//make ini
			$array = array();
			$array[] = "name=" . $config["name"];
			$array[] = "content=" . rawurlencode($config["content"]);
			
			file_put_contents($this->iniPath, implode("\n", $array));
			
			$funcName = str_replace(".", "_", substr($this->moduleId, strpos($this->moduleId, ".") + 1));
			
			//make php
			$array = array();
			$array[] = "<?php /* this script is generated by soyshop. */"."\n";
			$array[] = "function soyshop_" . $funcName . '($html,$htmlObj){'."\n";
			$array[] = "	ob_start();"."\n";
			$array[] = "?>";
			$array[] = trim($config["content"]);
			$array[] = "<?php"."\n";
			$array[] = "	ob_end_flush();" . "\n"; 
			$array[] = "}"."\n";
			$array[] = "?>";
			file_put_contents($this->modulePath, implode("", $array));
		}

		SOY2PageController::jump("Site.Template.Module.EditorPage?updated&moduleId=" . $this->moduleId);

	}

	function EditorPage($args){
		
		$this->moduleId = (isset($_GET["moduleId"])) ? htmlspecialchars(str_replace("/", ".", $_GET["moduleId"])) : null;

		$moduleDir = SOYSHOP_SITE_DIRECTORY . ".module/";
		
		$this->modulePath = $moduleDir . str_replace(".", "/", $this->moduleId) . ".php";
		$this->iniPath =$moduleDir . str_replace(".", "/", $this->moduleId) . ".ini";

		WebPage::WebPage();
		
		$ini = @parse_ini_file($this->iniPath);

		$this->addForm("update_form");
		
		$this->addLabel("module_name_text", array(
			"text" => (isset($ini["name"])) ? $ini["name"] : $this->moduleId
		));
		
		$this->addInput("module_name", array(
			"name" => "config[name]",
			"value" => ((isset($ini["name"]))) ? $ini["name"] : $this->moduleId
		));

		$this->addLabel("module_id", array(
			"text" => $this->moduleId
		));

		$content = (isset($ini["content"])) ? $ini["content"] : "";
		$this->addTextArea("module_content", array(
			"name" => "config[content]",
			"value" => $this->getModuleContent($content, file_get_contents($this->modulePath))
		));
		
		$this->addLabel("module_example", array(
			"text" => "<!-- shop:module=\"" . $this->moduleId."\" -->\n" . @$ini["name"] . "のモジュールを読み込みます。\n<!-- /shop:module=\"" . $this->moduleId."\" -->"
		));
	}
	
	function getModuleContent($ini, $str){
		if(strlen($ini) > 0){
			preg_match('/\?>(.*)<\?php/s', $str, $match);
			return (isset($match[1])) ? trim($match[1]) : "";
		}
		
		$array = array();
		$array[] = "<?php";
		$array[] = "//ここにモジュールとして読み込むHTML・PHPを記述してください。";
		$array[] = '//使用可能な変数';
		$array[] = '//     $html	テンプレートに記述されたHTML';
		$array[] = '//     $htmlObj	ページオブジェクト($htmlObj->createAdd()が使えます)';
		$array[] = "?>";
		
		return implode("\n", $array);
	}
}
?>