<?php
chdir(__DIR__);
function rec_scandir($pattern,$dir){
	$files=[];
	foreach(scandir($dir) as $fname){
		if($fname[0]==='.' || $fname[0]==='_' || strpos($fname,'.tmpl') || in_array($fname,['mailer','mailform','mailformpro','classes','vendor'])){continue;}
		if(is_dir($dir.'/'.$fname)){$files=array_merge($files,rec_scandir($pattern,$dir.'/'.$fname));}
		if(preg_match($pattern,$fname)){$files[]=$dir.'/'.$fname;}
	}
	return $files;
}
$files=rec_scandir('/\.(html|php)$/','httpdocs');
$csv=fopen('list.csv','w');
foreach($files as $file){
	fputcsv($csv,[$file]);
}
echo 'finish';