<?php
/**
Generate CSV listed all files in the directory.
*/
define('OUTPUT_DIRNAME_ONLY_ONCE',false);

ini_set('display_errors',1);
chdir(__DIR__);

function filter_dir($fname){
	//return  $fname[0]!=='_' && !in_array($fname,['vendor','classes','template'],true);
	return  $fname[0]!=='_';
}
function filter_file($fname){
	//return strrchr($fname,'.')==='.php' && substr($fname,-9)!=='.tmpl.php';
	return true;
}

function rec_scandir($csv,$dirs,$dirs_to_show){
	$files=[];
	$dir=implode('/',$dirs)?:'./';
	$is_first=true;
	foreach(scandir($dir) as $fname){
		if($fname==='.' || $fname==='..'){continue;}
		if(is_dir($dir.'/'.$fname)){
			if(filter_dir($fname)!==true){continue;}
			rec_scandir($csv,array_merge($dirs,[$fname]),array_merge($dirs_to_show,[$fname]));
		}
		else{
			if(filter_file($fname)!==true){continue;}
			fputcsv($csv,array_merge($dirs_to_show,[$fname]));
		}
		if($is_first){
			if(OUTPUT_DIRNAME_ONLY_ONCE){$dirs_to_show=array_map(function($val){return '';},$dirs_to_show);}
			$is_first=false;
		}
	}
	return $files;
}
$csv=fopen('listup_all_files.csv','w');
$files=rec_scandir($csv,[],[]);