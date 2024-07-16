<?php
if ( $_POST[ 'create' ] == 1 ) {
	$fp = dirname( __FILE__ );
	$txt_htaccess = sprintf( 'AuthUserFile "%1$s/.htpasswd"' . chr( 10 ) . 'AuthGroupFile /dev/null' . chr( 10 ) . 'AuthName "Please enter your ID and password"' . chr( 10 ) . 'AuthType Basic' . chr( 10 ) . 'require valid-user' . chr( 10 ), $fp );
	if ( !empty( $_POST[ 'filematch' ] ) ) {
		$txt_htaccess = sprintf( '<FilesMatch "%s">', $_POST[ 'filematch' ] ) . chr( 10 ) . $txt_htaccess . '</FilesMatch>' . chr( 10 );
	}
	if ( $_POST[ 'https_redirect' ] )$txt_htaccess .= '<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R,L]
</IfModule>';
	$txt_htpasswd = $_POST[ 'account' ] . ':' . crypt( $_POST[ 'password' ], 'zr' ) . chr( 10 );
	if ( !file_exists( $fp . '/.htaccess' ) ) {
		file_put_contents( $fp . '/.htaccess', $txt_htaccess );
		file_put_contents( $fp . '/.htpasswd', $txt_htpasswd );
	} else {
		$err_msg = '既に.htaccessが存在します';
	}
}
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Basic認証生成</title>
</head>

<body>
	<div class="input_box">
		<h1 class="input_box__title">Basic認証ファイル生成</h1>
		<div class="input_box__contents">
			<form action="basic_auth_gen.php" method="post">
				<input type="hidden" name="create" value="1"/>
				<table class="input_box__table">
					<tr>
						<th>アカウント</th>
						<td><input type="text" name="account"/></td>
					</tr>
					<tr>
						<th>パスワード</th>
						<td><input type="text" name="password"/></td>
					</tr>
					<tr>
						<th>対象ファイル<small>（正規表現で指定）</small></th>
						<td><input type="text" name="filematch"/></td>
					</tr>
					<tr>
						<td colspan="2"><input type="checkbox" name="https_redirect" value="1"/>
							httpsにリダイレクト</td>
					</tr>
				</table>
				<input class="btn" type="submit" value="認証ファイル作成"/>
			</form>
			<?php if(isset($err_msg))printf('<p class="err_msg">%s</p>'.chr(10),$err_msg); ?>
		</div>
	</div>
	<style>
		.input_box{
			margin:10px auto;
			padding:0;
			width:400px;
			border:solid 1px #888;
			border-radius:10px;
			overflow:hidden;
		}
		.input_box__title{
			margin:0;
			padding:5px;
			text-align:center;
			font-family:sans-serif;
			font-size:18px;
			color:#fff;
			background:#2E3D76;
		}
		.input_box__contents{
			padding:20px;
		}
		.input_box__table{
			margin:0 auto;
		}
		.input_box__table th{
			font-size:14px;
			font-family:sans-serif;
		}
		.input_box__table th small{
			display:block;
			font-size:10px;
			font-weight:normal;
		}
		input.btn{
			-webkit-appearance:none;
			appearance:none;
			display:block;
			border:none;
			border-radius:5px;
			background:#2E3D76;
			margin:10px auto;
			padding:5px 20px;
			font-size:18px;
			font-weight:bold;
			color:#fff;
		}
	</style>
</body>
</html>
