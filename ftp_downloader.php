<?php
class FTP{
	public $config,$connect,$root;
	function __construct($config){
		$this->init($config);
	}
	function init($config=null){
		if(isset($config)){$this->config=$config;}
		$this->connect=ftp_connect($this->config['host'],$this->config['port']??21);
		ftp_login($this->connect,$this->config['user'],$this->config['pass']);
		ftp_pasv($this->connect,true);
		$this->root=$this->config['root'];
		$this->local=$this->config['local']??__DIR__;
	}
	function ls($path=''){
		$files=ftp_mlsd($this->connect,$this->root.'/'.$path);
		$items=[];
		foreach($files as $file){
			if($file['type']==='dir' || $file['type']==='file'){$items[]=$file;}
		}
		return $items;
	}
	function get($file){
		error_log('download : '.$file);
		$this->create_dir(dirname($this->local.'/'.$file));
		return ftp_get($this->connect,$this->local.'/'.$file,$this->root.'/'.$file);
	}
	function create_dir($dir){
		if(!is_dir($pdir=dirname($dir))){
			$this->create_dir($pdir);
		}
		if(!is_dir($dir)){
			mkdir($dir);
		}
	}
	function get_dir($path){
		if(!is_dir($this->local.'/'.$path)){
			mkdir($this->local.'/'.$path);
		}
		foreach($this->ls($path) as $file){
			if($file['type']==='file'){
				$this->get($path.'/'.$file['name']);
			}
			elseif($file['type']==='dir'){
				$this->get_dir($path.'/'.$file['name']);
			}
		}
	}
	function close(){
		
	}
	function __wakeup(){
		$this->init();
	}
}
session_start();

if($req=json_decode(file_get_contents("php://input"),true)){
	switch($req['action']){
		case 'connect':
			$ftp=$_SESSION['ftp']=new FTP($req['config']);
			echo json_encode([
				'items'=>$ftp->ls()
			]);
			break;
		case 'get':
			echo json_encode([
				'result'=>$_SESSION['ftp']->get($req['file'])
			]);
			break;
		case 'scan':
			echo json_encode([
				'items'=>$_SESSION['ftp']->ls($req['dir'])
			]);
			break;
		case 'download':
			break;
	}
	die();
}
$config=$_SESSION['ftp']->config??[
	'host'=>'',
	'port'=>21,
	'user'=>'',
	'pass'=>'',
	'root'=>''
];
session_write_close();
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>FTPダウンロード</title>
<script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.0.0/dist/alpine-ie11.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<script>
	function props(){
		var con=axios.create({
			baseURL: '<?=$_SERVER['REQUEST_URI']?>'
		});
		const download_items=(dir,items,onUpdate)=>{
			return new Promise(async(resolve,reject)=>{
				for(var i=0;i<items.length;i++){
					var item=items[i];
					var file=(dir?dir+'/':'')+item.name;
					if(item.type==='file'){
						await con.post('',{
							action:'get',
							file
						})
						onUpdate(item,file);
					}
					else if(item.type==='dir'){
						var res=await con.post('',{
							action:'scan',
							dir:file
						});
						item.children=res.data.items;
						onUpdate();
						await download_items(file,item.children,onUpdate);
						delete item.children;
						onUpdate(item,file);
					}
				}
				resolve();
			});
		};
		const scan=(dir,items,onUpdate)=>{
			return new Promise(async(resolve,reject)=>{
				for(var i=0;i<items.length;i++){
					var item=items[i];
					var file=(dir?dir+'/':'')+item.name;
					if(item.type==='file'){
						onUpdate(item,file);
					}
					else if(item.type==='dir'){
						var res=await con.post('',{
							action:'scan',
							dir:file
						});
						await scan(file,res.data.items,onUpdate);
						onUpdate(item,file);
					}
				}
				resolve();
			});
		};
		const get_log=(items)=>{
			var rtn='<ul class="items">';
			items.map((item)=>{
				rtn+='<li class="item">'+{'dir':'📁',file:'📄'}[item.type]+'　'+item.name;
				if(item.children){
					rtn+=get_log(item.children);
				}
				rtn+='</li>';
			});
			rtn+='</ul>';
			return rtn;
		};
		return {
			host:'<?=$config['host']?>',
			port:'<?=$config['port']?>',
			user:'<?=$config['user']?>',
			pass:'<?=$config['pass']?>',
			root:'<?=$config['root']?>',
			phase:'input',
			items:[],
			info:{file:0,dir:0,size:0},
			done:{file:0,dir:0,size:0},
			connect(){
				con.post('',{action:'connect',config:{
					host:this.host,
					port:this.port,
					user:this.user,
					pass:this.pass,
					root:this.root
				}}).then((res)=>{
					this.items=res.data.items;
					this.done={file:0,dir:0,size:0};
					this.update_log();
					this.phase='confirm'
				});
			},
			download(){
				this.phase='download';
				scan('',this.items,(item,file)=>{
					if(undefined!==item){
						this.current=file;
						if(item.type==='file'){
							this.info.file++;
							this.info.size+=parseInt(item.size);
						}
						else if(item.type==='dir'){
							this.info.dir++;
						}
					}
				}).then(()=>{
					return download_items('',this.items,(item,file)=>{
						if(undefined!==item){
							this.current=file;
							if(item.type==='file'){
								this.done.file++;
								this.done.size+=parseInt(item.size);
								this.progress=parseInt(this.done.size/this.info.size*1000)/10;
							}
							else if(item.type==='dir'){
								this.done.dir++;
							}
						}
						this.update_log();
					});
				}).then(()=>{
					this.current='Finish';
				});
			},
			log:'',
			current:'',
			progress:0,
			update_log(){
				
				this.log=get_log(this.items);
			}
		};
	}
</script>
<style>
	.log{
		padding:10px;margin:10px;border-radius:10px;
		background:#eee;
		color:#888;
	}
	.log .items{
		list-style:none;
	}
	.log .item .items{
		padding-left:10px;margin-left:10px;
		border-left:solid 5px #ddd;
	}
	.log .item{
		
	}
</style>
</head>

<body>
	<div x-data="props();">
		<div class="container m-4 mx-auto">
			<div class="card text-center">
				<h3 class="card-header">FTPダウンロード</h3>
				<div class="card-body">
					<template x-if="phase=='input' || phase=='confirm'">
						<table class="table" >
							<tr>
								<th>ホスト</th>
								<th>ポート</th>
								<th>ユーザー</th>
								<th>パスワード</th>
								<th>ディレクトリ</th>
							</tr>
							<tr>
								<td><input class="form-control" type="text" name="host" x-model="host" placeholder="host"/></td>
								<td><input class="form-control" type="text" name="port" x-model="port" size="5" placeholder="port"/></td>
								<td><input class="form-control" type="text" name="user" x-model="user" placeholder="user"/></td>
								<td><input class="form-control" type="text" name="pass" x-model="pass" placeholder="pass"/></td>
								<td><input class="form-control" type="text" name="root" x-model="root" placeholder="root"/></td>
							</tr>
						</table>
					</template>
					<template x-if="phase=='input'">
						<div class="btn btn-primary" @click="connect()">接続</div>
					</template>
					<template x-if="phase=='confirm'">
						<div role="group" aria-label="Basic example">
							<button type="button" class="btn btn-secondary" @click="connect()">再接続</button>
							<button type="button" class="btn btn-primary" @click="download()">ダウンロード開始</button>
						</div>
					</template>
					<template x-if="phase=='download'">
						<span x-text="(done.size>>10)/1000"></span>MB/<span x-text="(info.size>>10)/1000"></span>MB　
						File:<span x-text="done.file"></span>/<span x-text="info.file"></span>　
						Dir:<span x-text="done.dir"></span>/<span x-text="info.dir"></span>
						<div role="group" aria-label="Basic example">
						<div class="progress m-4 mx-auto">
						  <div class="progress-bar" role="progressbar" :style="'width:'+progress+'%'" x-text="progress+'%'" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
						<span x-text="current"></span>
					</template>
					<div class="log text-left" x-html="log"></div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
