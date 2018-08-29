<?php
/*+*******************************************************************************
 * This script is to create custom modulle by  providing a parameter which will be the module name, and one more parameter the menu name.
 *It will create a new module with that name , and one auto generated field for numbering.
 ********************************************************************************/

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors','1');
//Vtiger_Module provides an API to work with vtiger CRM modules.
include_once 'vtlib/Vtiger/Module.php';
//required file to add comment field on summary page
require_once 'modules/ModComments/ModComments.php';

//to debug databse query
///global $adb;
//$adb->setDebug(true);
//get current version of vtiger
include_once 'vtlib/Vtiger/Version.php';
$version_get = new Vtiger_Version();
//compare basic module directory name with version
$files = glob(getcwd().'/vtlib/ModuleDir/'. "*");
	foreach($files as $file)
	{
	 //check to see if the file is a folder/directory
	 if(is_dir($file))
	 {
		if(count($files)<=1)
		{
			$basic_folder = $file;
		}else
		{
			if($file==$version_get)
			{
				$basic_folder = $file;
			}
		}
	 }
	}

$Vtiger_Utils_Log = true;
//dynamically get module name
//get module name from querystring
if(isset($_REQUEST['module_name']) && $_REQUEST['module_name']!="")
{
	$MODULENAME = ucfirst($_REQUEST['module_name']);

	//create instance with modulename
	$moduleInstance = Vtiger_Module::getInstance($MODULENAME);
	//function to get class name in from module which we have created
	function get_php_classes($php_code) {
	  $classes = array();
	  $tokens = token_get_all($php_code);
	  $count = count($tokens);
	  for ($i = 2; $i < $count; $i++) {
	    if (   $tokens[$i - 2][0] == T_CLASS
		&& $tokens[$i - 1][0] == T_WHITESPACE
		&& $tokens[$i][0] == T_STRING) {

		$class_name = $tokens[$i][1];
		$classes[] = $class_name;
	    }
	  }
	  return $classes;
	}

	//rename class name with modulename
	function rename_class_name($new_filename,$modulename)
	{
		$content = file_get_contents($new_filename);
		//get php class name.Function define above
		$classes = get_php_classes($content);
		$lines = file($new_filename);
		chmod($new_filename, 0777); 
		// Loop through our array, show HTML source as HTML source; and line numbers too.
		foreach ($lines as $line_num => $line) {
			$content =  $line;
			$arr = str_replace($classes,$modulename,$content);
			$arr_data = str_replace('<modulename>',strtolower($modulename),$arr);
			$get_content[]=$arr_data;
		}
		if(file_put_contents($new_filename, $get_content))
		return true;

	}

	//changes in language file
	function lang_file($new_filename,$modulename)
	{
		 $file_handle = fopen($new_filename, "r");
		 while (!feof($file_handle)) {
		       $line = fgets($file_handle);
			$old_phrase = array("Module Name", "SINGLE_ModuleName", "ModuleBlock Information", "MODULEBLOCK");
			$new_phrase   = array($modulename, 'SINGLE_'.$modulename, $modulename,strtoupper($modulename));
			$newphrase[] = str_replace($old_phrase, $new_phrase, $line);
		}
		 if($newphrase)
		 if(file_put_contents($new_filename, $newphrase))
		 return true;
	}

	//changes in tpl file
	function tpl_file($destination,$modulename,$str_to_replace,$str_with_replace)
	{
		 $file_handle = fopen($destination, "r");
		 while (!feof($file_handle)) {
		   $line = fgets($file_handle);
		  $data1[]= str_replace($str_to_replace,$str_with_replace,$line);
		}
		 if($data1)
		 if(file_put_contents($destination, $data1))
		return true;
	}

	//function to copy Basic module directory to module which we want to create
	// provide permission to files.
	//rename file with module name
	//rename class name with module name
	function copy_directory($src,$dst,$modulename) {
	    $dir = opendir($src);
	    @mkdir($dst);
	    @mkdir($dst.'/views/');
	    chmod($dst.'/views/', 0777);
	    while(false !== ( $file = readdir($dir)) ) {
	       
		
		if (( $file != '.' ) && ( $file != '..' )) {
			copy($src . '/' . $file,$dst . '/' . $file);
			
			//create a file inside view (detail.php and add class whcih extends to account detial view to add activity widget in module)
			$myfile = fopen($dst."/views/Detail.php", "w");
			$txt = "<?php class ".$modulename."_Detail_View extends Accounts_Detail_View { } ?>";
			fwrite($myfile, $txt);
			fclose($myfile);	
			$fn = $dst."/ModuleName.php";
			$newfn = $dst."/".$modulename.".php";
			//rename file name with module name we want to create
			 rename($fn,$newfn); 
			//rename class anem with module name and put content in file
			if(rename_class_name($newfn,$modulename))
			{
			  //language file are in root folder languages, so copy file and move here.
			   $source2= getcwd();
			    if ( is_dir($src) ) {
				 $dir2 = opendir($src.'/languages');
				while(false !== ( $file2 = readdir($dir2)) ) {
				        if (( $file2 != '.' ) && ( $file2 != '..' )) {
				            $dir3 = opendir($src.'/languages/en_us');
				            while(false !== ( $file3 = readdir($dir3)) ) {
				                       copy($src.'/languages/en_us/ModuleName.php',$source2.'/languages/en_us/ModuleName.php');
							$fn = $source2."/languages/en_us/ModuleName.php";
							$newfn = $source2."/languages/en_us/".$modulename.".php";
							//rename file name with module name we want to create
							rename($fn,$newfn);
							if(lang_file($newfn,$modulename)) 
							{
								//create .tpl file for adding comments, updates and activities on summary page
								//.tpl file created in folder layouts/vlayout/modules/modulename
								///copy file fron layouts/vlayout/Vtiger/DetailViewSummaryContents.tpl to yoir module
								
								$source = getcwd().'/layouts/vlayout/modules/Vtiger/DetailViewSummaryContents.tpl';
								$destination = getcwd().'/layouts/vlayout/modules/'.$modulename.'/DetailViewSummaryContents.tpl';
								$dess = getcwd().'/layouts/vlayout/modules/'.$modulename;
								@mkdir($dess);
								chmod($dess, 0777); 
								if(copy($source,$destination))
								{	
									$str_to_replace = 'DetailViewFullContents';$str_with_replace = 'SummaryViewWidgets';
									tpl_file($destination,$modulename,$str_to_replace,$str_with_replace);
									$source = getcwd().'/layouts/vlayout/modules/Vtiger/ModuleSummaryView.tpl';
									$destination = getcwd().'/layouts/vlayout/modules/'.$modulename.'/ModuleSummaryView.tpl';
									chmod($dess, 0777); 
									if(copy($source,$destination)) 
									$str_to_replace = "DetailViewBlockView";
									$str_with_replace = "SummaryViewContents";
									if(tpl_file($destination,$modulename,$str_to_replace,$str_with_replace))
									return true;
									
								}
							}
				            }
				        }
				}
			    }
		    	}else
			{
				return false;
			}
		}
	    }
	    closedir($dir);
	}


	if (file_exists('modules/'.$MODULENAME)) {
		echo "Module already present.choose a different name.";
	} else {
		//Class Vtiger_Module provides an API to work with vtiger CRM modules.
		 $moduleInstance = new Vtiger_Module();
		 $moduleInstance->name = $MODULENAME;
		 $moduleInstance->save();
		//initTables() API will initialize (create) the 2 necessary tables
		$moduleInstance->initTables();
		$menuInstance = Vtiger_Menu::getInstance('Tools');
		//addModule(<ModuleInstance>) API will create menu item which serves as UI entry point for the modul (This will create menu under Tools)
		$menuInstance->addModule($moduleInstance);

		//Class Vtiger_Block provides API to work with a Module block
		//$blockInstance = new Vtiger_Block();
		///$blockInstance->label = 'LBL_'.strtoupper($MODULENAME).'_INFORMATION';
		//$moduleInstance->addBlock($blockInstance);
		//you can see block in table vtiger_blocks

		//Class Vtiger_Block provides API to work with a Module block
		$blockInstance1 = new Vtiger_Block();
		$blockInstance1->label = 'LBL_'.strtoupper($MODULENAME).'_INFORMATION';
		$moduleInstance->addBlock($blockInstance1);
		//you can see block in table vtiger_blocks

		$blockInstance2 = new Vtiger_Block();
		//LBL_CUSTOM_INFORMATION block should always be created to support Custom Fields for a module.
		$blockInstance2->label = 'LBL_CUSTOM_INFORMATION';
		//you can see block in table vtiger_blocks
		$moduleInstance->addBlock($blockInstance2);


		//Adding Fields
		$fieldInstance = new Vtiger_Field();
		$fieldInstance->name = strtolower($MODULENAME.'Name');
		$fieldInstance->label = $MODULENAME.' Name';
		$fieldInstance->table = 'vtiger_'.strtolower($MODULENAME);
		$fieldInstance->column = strtolower($MODULENAME).'name';
		$fieldInstance->columntype = 'VARCHAR(255)';
		$fieldInstance->uitype = 2;
		$fieldInstanc->displaytype= 1;
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance->summaryfield = '1';
		$fieldInstance->typeofdata = 'V~M';
		//this will ad fields in your moduel tables
		$blockInstance1->addField($fieldInstance);
		//One of the mandatory field should be set as entity identifier of module once it is created. This field
		//will be used for showing the details in 'Last Viewed Entries
		$moduleInstance->setEntityIdentifier($fieldInstance);
		
		$fieldInstance1 = new Vtiger_Field();
		$fieldInstance1->name = strtolower($MODULENAME.'Number');
		//Module’s basetable
		$fieldInstance1->label = $MODULENAME.' Number';
		$fieldInstance1->table = 'vtiger_'.strtolower($MODULENAME);
		//$fieldInstance->name in lowercase
		$fieldInstance1->column = strtolower($MODULENAME).'_number';
		$fieldInstance1->columntype = 'VARCHAR(100)';
		$fieldInstance1->uitype = 4;
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance1->typeofdata = 'v~o';
		//this will ad fields in your moduel tables
		$blockInstance1->addField($fieldInstance1);

		//GET FIRST three character of modulename
		 $mod_first_three = substr($MODULENAME,0,3);
		// Set the Auto Sequence No. 
		$entity_tmp = new CRMEntity(); 
		$entity_tmp->setModuleSeqNumber('configure', $MODULENAME, strtoupper($mod_first_three), 1); 		


		$fieldInstance2 = new Vtiger_Field();
		$fieldInstance2->name = strtolower($MODULENAME.'Email');
		$fieldInstance2->label = $MODULENAME.' Email';
		$fieldInstance2->table = 'vtiger_'.strtolower($MODULENAME);
		$fieldInstance2->column = strtolower($MODULENAME).'email';
		$fieldInstance2->columntype = 'VARCHAR(255)';
		$fieldInstance2->uitype =13;
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance2->typeofdata = 'V~M';
		$fieldInstance2->summaryfield = '1';
		//this will ad fields in your moduel tables
		$blockInstance1->addField($fieldInstance2);


		$fieldInstance3 = new Vtiger_Field();
		$fieldInstance3->name = strtolower($MODULENAME.'Address');
		$fieldInstance3->label = $MODULENAME.' Address';
		$fieldInstance3->table = 'vtiger_'.strtolower($MODULENAME);
		$fieldInstance3->column = strtolower($MODULENAME).'address';
		$fieldInstance3->columntype = 'VARCHAR(255)';
		$fieldInstance3->uitype = 2;
		$fieldInstance3->summaryfield = '1';
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance3->typeofdata = 'V~M';
		//this will ad fields in your moduel tables
		$blockInstance1->addField($fieldInstance3);

		
		$fieldInstance4 = new Vtiger_Field();
		$fieldInstance4->name = strtolower($MODULENAME.'Country');
		$fieldInstance4->label = $MODULENAME.' Country';
		$fieldInstance4->table = 'vtiger_'.strtolower($MODULENAME);
		$fieldInstance4->column = strtolower($MODULENAME).'country';
		$fieldInstance4->columntype = 'VARCHAR(255)';
		//Set Picklist Values
		$fieldInstance4->uitype = 1;
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance4->typeofdata = 'V~O';
		//this will ad fields in your moduel tables
		$blockInstance2->addField($fieldInstance4);

		$fieldInstance5 = new Vtiger_Field();
		$fieldInstance5->name = strtolower($MODULENAME.'DOB');
		$fieldInstance5->label = $MODULENAME.' DOB';
		$fieldInstance5->table = 'vtiger_'.strtolower($MODULENAME);
		$fieldInstance5->column = strtolower($MODULENAME).'dob';
		$fieldInstance5->columntype = 'Date';
		//Set Picklist Values
		$fieldInstance5->uitype = 5;
		//if you wnat to set field as mendatory set as V~M if as optional set as V~O
		// here we are setting field as mendatory
		$fieldInstance5->typeofdata = 'D~O';
		//this will ad fields in your moduel tables
		$blockInstance2->addField($fieldInstance5);

		 // Recommended common fields every Entity module should have (linked to core table)
		$fieldInstance6 = new Vtiger_Field();
		$fieldInstance6->name = 'CreatedTime';
		$fieldInstance6->label= 'Created Time';
		$fieldInstance6->table = 'vtiger_crmentity';
		$fieldInstance6->column = 'createdtime';
		$fieldInstance6->uitype = 70;
		$fieldInstance6->typeofdata = 'T~O';
		$fieldInstance6->displaytype= 2;
		$blockInstance1->addField($fieldInstance6);

		$fieldInstance7 = new Vtiger_Field();
		$fieldInstance7->name = 'ModifiedTime';
		$fieldInstance7->label= 'Modified Time';
		$fieldInstance7->table = 'vtiger_crmentity';
		$fieldInstance7->column = 'modifiedtime';
		$fieldInstance7->uitype = 70;
		$fieldInstance7->typeofdata = 'T~O';
		$fieldInstance7->displaytype= 2;
		$blockInstance1->addField($fieldInstance7);

		$fieldInstance8 = new Vtiger_Field();
		$fieldInstance8->name = 'assigned_user_id';
		$fieldInstance8->label = 'Assigned To';
		$fieldInstance8->table = 'vtiger_crmentity';
		$fieldInstance8->column = 'smownerid';
		$fieldInstance8->uitype = 53;
		$fieldInstance8->typeofdata = 'V~M';
		$blockInstance1->addField($fieldInstance8);
		
		//add comments
		$commentsModule = Vtiger_Module::getInstance('ModComments');
		$fieldInstance = Vtiger_Field::getInstance('related_to', $commentsModule);
		$fieldInstance->setRelatedModules(array($MODULENAME));// Give the Module name for which you want to add comment
		$detailviewblock = ModComments::addWidgetTo($MODULENAME);//Give the Module name for which you want to add comment
	
		//add activity link
		$accountsModule = Vtiger_Module::getInstance('Calendar');
		$moduleInstance->setRelatedList($accountsModule, 'Activity', Array('ADD','SELECT'));
	
		//add this to add comments and get activity on detail page
		include_once('modules/ModTracker/ModTracker.php');
		$module = vtiger_module::getinstance($MODULENAME);
		ModTracker::enabletrackingformodule($module->id);

		//creating filter
		$filterInstance = new Vtiger_Filter();
		$filterInstance->name = 'All';
		$filterInstance->isdefault = true;
		$moduleInstance->addFilter($filterInstance);
		//add fields to the filter 
		//$filterInstance->addField($fieldInstance, $columnIndex);
		$filterInstance->addField($fieldInstance1)->addField($fieldInstance,1)->addField($fieldInstance2, 2)->addField($fieldInstance3,3)->addField($fieldInstance4, 4)->addField($fieldInstance8, 5);

		// Sharing Access Setup
		$moduleInstance->setDefaultSharing('Private');
		// Webservice Setup here
		$moduleInstance->initWebservice();
		/** Enable and Disable available tools */
		$moduleInstance->enableTools(Array('Import', 'Export'));
		//$moduleInstance->disableTools('Merge');
		
			if(mkdir('modules/'.$MODULENAME))
			{
				chmod('modules/'.$MODULENAME, 0777); 
				if(file_exists('modules/'.$MODULENAME))
				{	
				   if(copy_directory($basic_folder,getcwd().'/modules/'.ucfirst($MODULENAME),$MODULENAME))
				   {
				      $status = copy_directory($basic_folder,getcwd().'/modules/'.ucfirst($MODULENAME),$MODULENAME);
				      header('Location: createcustom_module_view.php?action='.$status);
				   }
				}else
				  {
				 	die("Dont have permission to create module files");
		
				  }
			}else
	         	   {
		 	die("Dont have permission to create module files");
		
		 	 }
	}
}else
{
	die('Please enter Module name');
}

?>
