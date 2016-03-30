<?php
/**
 * @class Site.Template.EditorPage
 * @date 2009-11-27T03:36:27+09:00
 * @author SOY2HTMLFactory
 */
class CreatePage extends WebPage{

	private $moduleId;
	private $modulePath;
	private $iniPath;
	private $moduleName;

	function doPost(){
		
		$this->moduleId = (isset($_POST["moduleId"])) ? htmlspecialchars($_POST["moduleId"]) : null;
		$this->moduleName = $_POST["moduleName"];
		if(strlen($this->moduleName) < 1) $this->moduleName = $this->moduleId;

		$moduleDir = SOYSHOP_SITE_DIRECTORY . ".module/html/";
		
		$this->modulePath = $moduleDir . str_replace(".", "/", $this->moduleId) . ".php";
		$this->iniPath = $moduleDir . str_replace(".", "/", $this->moduleId) . ".ini";
		
		if(soy2_check_token()){
			if(preg_match('/^[a-zA-Z0-9_]+$/', $this->moduleId) && !file_exists($this->modulePath)){
				@mkdir(dirname($this->modulePath), 0766, true);
				file_put_contents($this->modulePath, "<?php ?>");
				file_put_contents($this->iniPath, "name=" . $this->moduleName);
				
				SOY2PageController::jump("Site.Template.Module.html.EditorPage?updated&moduleId=" . $this->moduleId);
			}else{
				//
			}
		}
	}

	function CreatePage($args){
	
		WebPage::WebPage();
		
		if($this->moduleId) DisplayPlugin::visible("failed");
		
		$this->addForm("create_form");
		
		$this->addInput("module_id", array(
			"name" => "moduleId",
			"value" => $this->moduleId
		));
		
		$this->addInput("module_name", array(
			"name" => "moduleName",
			"value" => $this->moduleName
		));
	}
}
?>