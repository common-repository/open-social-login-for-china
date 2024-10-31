<?php
/**
 * Plugin Name: Open Social Login for China 国内社交网站登陆
 * Plugin URI: http://www.xiaomac.com/201311150.html
 * Description: 主要针对国内，可用腾讯QQ、新浪微博、百度、谷歌登录网站并绑定帐号的一个插件，无第三方平台、无接口文件冗余、带昵称网址头像等；设置简单，绿色低碳。适合博主不开放注册、游客无缝登陆、不喜第三方平台接入、手动折腾能力强的朋友。欢迎多提意见，谢谢。
 * Author: Afly
 * Author URI: http://www.xiaomac.com/
 * Version: 1.0.1
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

include_once( 'setting.php' );
if (!session_id()) session_start();
$path = WP_PLUGIN_URL . '/' . dirname(plugin_basename (__FILE__));

add_action('init', 'open_init', 1);
function open_init() {
	if (isset($_GET['connect'])) {
		define('OPEN_TYPE',$_GET['connect']);
		if(OPEN_TYPE=='qq'){
			$osl = new QQ_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				if ($_GET['state'] == $_SESSION['state']) {
					$osl -> open_callback($_GET['code']);
					open_action($osl);
				} 
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			} 
		}elseif(OPEN_TYPE=='sina'){
			$osl = new SINA_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			} else if ($_GET['action'] == 'update'){
				open_update_test($_GET['text']);
			}
		}elseif(OPEN_TYPE=='baidu'){
			$osl = new BAIDU_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}elseif(OPEN_TYPE=='google'){
			$osl = new GOOGLE_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}elseif(OPEN_TYPE=='live'){
			$osl = new LIVE_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}elseif(OPEN_TYPE=='douban'){
			$osl = new DOUBAN_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}elseif(OPEN_TYPE=='renren'){
			$osl = new RENREN_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}elseif(OPEN_TYPE=='kaixin'){
			$osl = new KAIXIN_CLASS();
			if ($_GET['action'] == 'login') {
				$osl -> open_login();
			} else if ($_GET['action'] == 'callback') {
				$osl -> open_callback($_GET['code']);
				open_action($osl);
			} else if ($_GET['action'] == 'unbind') {
				open_unbind();
			}
		}
	} 
} 

class QQ_CLASS {
	function open_login() {
		$_SESSION['state'] = md5(uniqid(rand(), true));
		$params=array(
			'response_type'=>'code',
			'client_id'=>QQ_AKEY,
			'state'=>$_SESSION['state'],
			'scope'=>'get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo',
			'redirect_uri'=>QQ_BACK.'?connect=qq&action=callback'
		);
		header('Location:https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>QQ_AKEY,
			'client_secret'=>QQ_SKEY,
			'redirect_uri'=>QQ_BACK.'?connect=qq&action=callback'
		);
		$str = file_get_contents('https://graph.qq.com/oauth2.0/token?'.http_build_query($params));
        $token = array();
        parse_str($str, $token);
		$_SESSION['access_token'] = $token['access_token'];
		$str = file_get_contents("https://graph.qq.com/oauth2.0/me?access_token=".$_SESSION['access_token']);
		//$url = 'http://fusion.qq.com/cgi-bin/qzapps/userapp_redirect.cgi?pf=qzone&appid='.QQ_AKEY.'&openid='.$user -> openid;
		if (strpos($str, "callback") !== false) {
			$lpos = strpos($str, "(");
			$rpos = strrpos($str, ")");
			$str = substr($str, $lpos + 1, $rpos - $lpos -1);
		} 
		$ret = json_decode($str);
		if (isset($ret -> error)) open_close("<h3>error:</h3>" . $ret -> error . "<h3>msg  :</h3>" . $ret -> error_description);
		$_SESSION['open_id'] = $ret -> openid;
	} 
	function open_new_user(){
		$str = open_connect_http('https://graph.qq.com/user/get_user_info?access_token='.$_SESSION['access_token'].'&oauth_consumer_key='.QQ_AKEY.'&openid='.$_SESSION['open_id']);
		$nickname = $str['nickname'];
		$str = open_connect_http('https://graph.qq.com/user/get_info?access_token='.$_SESSION['access_token'].'&oauth_consumer_key='.QQ_AKEY.'&openid='.$_SESSION['open_id']);
		$name = $str['data']['name'];//t.qq.com/***
		return array(
			'nickname' => $nickname,
			'display_name' => $nickname,
			'user_url' => 'http://t.qq.com/'.$name,
			'user_email' => $name.'@t.qq.com'
		);		
	}
} 

class SINA_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>WB_AKEY,
			'redirect_uri'=>WB_BACK.'?connect=sina&action=callback'
		);
		header('Location:https://api.weibo.com/oauth2/authorize?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>WB_AKEY,
			'client_secret'=>WB_SKEY,
			'redirect_uri'=>WB_BACK.'?connect=sina&action=callback'
		);
		$str = open_connect_http('https://api.weibo.com/oauth2/access_token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$_SESSION['open_id'] = $str["uid"];
	}
	function open_new_user(){
		$user = open_connect_http("https://api.weibo.com/2/users/show.json?access_token=".$_SESSION["access_token"]."&uid=".$_SESSION['open_id']);
		return array(
			'nickname' => $user['screen_name'],
			'display_name' => $user['screen_name'],
			'user_url' => 'http://weibo.com/'.$user['profile_url'],
			'user_email' => $_SESSION['open_id'].'@weibo.com'
		);
	} 
} 

class BAIDU_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>BD_AKEY,
			'redirect_uri'=>BD_BACK.'?connect=baidu&action=callback',
			'scope'=>'basic',
			'display'=>'page'
		);
		header('Location:https://openapi.baidu.com/oauth/2.0/authorize?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>BD_AKEY,
			'client_secret'=>BD_SKEY,
			'redirect_uri'=>BD_BACK.'?connect=baidu&action=callback'
		);
		$str = open_connect_http('https://openapi.baidu.com/oauth/2.0/token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$user = open_connect_http("https://openapi.baidu.com/rest/2.0/passport/users/getLoggedInUser?access_token=".$_SESSION["access_token"]);
		$_SESSION['open_id'] = $user['portrait'];//头像字段，基本唯一
	}
	function open_new_user(){
		$user = open_connect_http("https://openapi.baidu.com/rest/2.0/passport/users/getLoggedInUser?access_token=".$_SESSION["access_token"]);
		return array(
			'nickname' => $user["uname"],
			'display_name' => $user["uname"],
			'user_url' => 'http://www.baidu.com/p/'.$user['uname'],
			'user_email' => $user["uid"].'@baidu.com'
		);
	}
} 

class GOOGLE_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>GG_AKEY,
			'scope'=>'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
			'redirect_uri'=> GG_BACK,
			'state'=>'profile',
			'access_type'=>'offline'
		);
		header('Location:https://accounts.google.com/o/oauth2/auth?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>GG_AKEY,
			'client_secret'=>GG_SKEY,
			'redirect_uri'=>GG_BACK
		);
		$str = open_connect_http('https://accounts.google.com/o/oauth2/token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$user = open_connect_http("https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$_SESSION["access_token"]);
		$_SESSION['open_id'] = $user["id"];
	}
	function open_new_user(){
		$user = open_connect_http("https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$_SESSION["access_token"]);
		return array(
			'nickname' => $user['name'],
			'display_name' => $user['name'],
			'user_url' => 'http://plus.google.com/'.$_SESSION['open_id'],
			'user_email' => $user["email"]//this one is real
		);
	}
} 

class LIVE_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>WL_AKEY,
			'redirect_uri'=>WL_BACK.'?connect=live&action=callback',
			'scope'=>'wl.signin wl.basic wl.emails'
		);
		header('Location:https://login.live.com/oauth20_authorize.srf?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>WL_AKEY,
			'client_secret'=>WL_SKEY,
			'redirect_uri'=>WL_BACK.'?connect=live&action=callback'
		);
		$str = open_connect_http('https://login.live.com/oauth20_token.srf', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$user = open_connect_http("https://apis.live.net/v5.0/me");//?access_token=".$_SESSION["access_token"]
		$_SESSION['open_id'] = $user["id"];
	}
	function open_new_user(){
		$user = open_connect_http("https://apis.live.net/v5.0/me");
		return array(
			'nickname' => $user["name"],
			'display_name' => $user["name"],
			'user_url' => 'https://profile.live.com/cid-'.$_SESSION['open_id'],
			'user_email' => $user['emails']['preferred']
		);
	}
} 

class DOUBAN_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>DB_AKEY,
			'redirect_uri'=>DB_BACK.'?connect=douban&action=callback',
			'scope'=>'shuo_basic_r,shuo_basic_w,douban_basic_common',
			'state'=>md5(time())
		);
		header('Location:https://www.douban.com/service/auth2/auth?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>DB_AKEY,
			'client_secret'=>DB_SKEY,
			'redirect_uri'=>DB_BACK.'?connect=douban&action=callback'
		);
		$str = open_connect_http('https://www.douban.com/service/auth2/token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$_SESSION['open_id'] = $str["douban_user_id"];
	}
	function open_new_user(){
		$user = open_connect_http("https://api.douban.com/v2/user/~me?access_token=".$_SESSION["access_token"]);
		return array(
			'nickname' => $user['name'],
			'display_name' => $user['name'],
			'user_url' => 'http://www.douban.com/people/'.$_SESSION['open_id'].'/',
			'user_email' => $_SESSION['open_id'].'@douban.com'
		);
	}
} 

class RENREN_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>RR_AKEY,
			'redirect_uri'=>RR_BACK.'?connect=renren&action=callback',
			'scope'=>'status_update read_user_status'
		);
		header('Location:https://graph.renren.com/oauth/authorize?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>RR_AKEY,
			'client_secret'=>RR_SKEY,
			'redirect_uri'=>RR_BACK.'?connect=renren&action=callback'
		);
		$str = open_connect_http('https://graph.renren.com/oauth/token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$_SESSION['open_id'] = $str["user"]["id"];
	}
	function open_new_user(){
		$user = open_connect_http("https://api.renren.com/v2/user/login/get?access_token=".$_SESSION["access_token"]);
		$_SESSION['open_img'] = $user['response']["avatar"][0]['url'];
		return array(
			'nickname' => $user['response']['name'],
			'display_name' => $user['response']['name'],
			'user_url' => 'http://www.renren.com/home?id='.$_SESSION['open_id'],
			'user_email' => $_SESSION['open_id'].'@renren.com'
		);
	}
} 

class KAIXIN_CLASS {
	function open_login() {
		$params=array(
			'response_type'=>'code',
			'client_id'=>KX_AKEY,
			'redirect_uri'=>KX_BACK.'?connect=kaixin&action=callback',
			'scope'=>'basic'
		);
		header('Location:http://api.kaixin001.com/oauth2/authorize?'.http_build_query($params));
		exit();
	} 
	function open_callback($code) {
		$params=array(
			'grant_type'=>'authorization_code',
			'code'=>$code,
			'client_id'=>KX_AKEY,
			'client_secret'=>KX_SKEY,
			'redirect_uri'=>KX_BACK.'?connect=kaixin&action=callback'
		);
		$str = open_connect_http('https://api.kaixin001.com/oauth2/access_token', http_build_query($params), 'POST');
		$_SESSION["access_token"] = $str["access_token"];
		$user = open_connect_http("https://api.kaixin001.com/users/me?access_token=".$_SESSION["access_token"]);
		$_SESSION['open_id'] = $user["uid"];
	}
	function open_new_user(){
		$user = open_connect_http("https://api.kaixin001.com/users/me?access_token=".$_SESSION["access_token"]);
		$_SESSION['open_img'] = $user['logo50'];
		return array(
			'nickname' => $user['name'],
			'display_name' => $user['name'],
			'user_url' => 'http://www.kaixin001.com/home/'.$_SESSION['open_id'].'.html',
			'user_email' => $_SESSION['open_id'].'@kaixin.com'
		);
	}
} 

//显示错误并终止操作
function open_close($open_info){
	wp_die($open_info);
	exit();
}

//绑定帐号
function open_isbind($open_id) {
	global $wpdb;
	$sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
	return $wpdb -> get_var($wpdb -> prepare($sql, 'open_id', $open_id));
} 

//取消绑定
function open_unbind(){
	if (is_user_logged_in()) {
		$user = wp_get_current_user();
		delete_user_meta($user -> ID, 'open_type');
		delete_user_meta($user -> ID, 'open_img');
		delete_user_meta($user -> ID, 'open_id');
		delete_user_meta($user -> ID, 'open_access_token');
	}
	echo '<script>opener.window.focus();opener.window.location.reload();window.close();</script>';
	exit;
}

//公共登陆模块
function open_action($osl){
	if (!$_SESSION['open_id'] || !OPEN_TYPE) return;
	if (is_user_logged_in()) {
		$wpuid = get_current_user_id();
		if (open_isbind($_SESSION['open_id'])) {
			open_close('此帐号已被网站其他用户绑定，请先取消绑定。');
		}else{
			$open_id = get_user_meta($wpuid, 'open_id', true);
			if ($open_id) open_close('你已绑定了其他的帐号，请先取消绑定。');
		}
	} else {
		$wpuid = open_isbind($_SESSION['open_id']);
		if (!$wpuid) {
			$wpuid = username_exists(strtoupper(OPEN_TYPE).$_SESSION['open_id']);
			if(!$wpuid){
				$userdata = array(
					'user_pass' => wp_generate_password(),
					'user_login' => strtoupper(OPEN_TYPE).$_SESSION['open_id'],
					'show_admin_bar_front' => 'false'
				);
				$userdata = array_merge($userdata, $osl -> open_new_user());
				if(email_exists($userdata['user_email'])) open_close('你的邮箱已绑定了其他的帐号，请先取消绑定。');//谷歌和微软真实邮箱的烦恼:(
				if(!function_exists('wp_insert_user')){
					include_once( ABSPATH . WPINC . '/registration.php' );
				} 
				$wpuid = wp_insert_user($userdata);
			}
		} 
	} 
	if($wpuid){
		update_user_meta($wpuid, 'open_type', OPEN_TYPE);
		if(isset($_SESSION['open_img'])) update_user_meta($wpuid, 'open_img', $_SESSION['open_img']);
		update_user_meta($wpuid, 'open_id', $_SESSION['open_id']);
		update_user_meta($wpuid, 'open_access_token', $_SESSION["access_token"]);
		wp_set_auth_cookie($wpuid, true, false);
		wp_set_current_user($wpuid);
	}
	unset($_SESSION['open_id']);
	unset($_SESSION["access_token"]);
	if(isset($_SESSION['open_img'])) unset($_SESSION['open_img']); 
	if(isset($_SESSION['state'])) unset($_SESSION['state']); 
	echo '<script>opener.window.focus();opener.window.location.reload();window.close();</script>';
	exit;	
}

//发布微博
function open_update_test($text){
	$params=array(
		'status'=>$text
	);
	$re = open_connect_api('https://api.weibo.com/2/statuses/update.json', $params, 'POST');
	echo '<script>alert("发布成功");opener.window.focus();window.close();</script>';
	exit;
}

function open_connect_api($url, $params=array(), $method='GET'){
	$user = wp_get_current_user();
	$access_token = get_user_meta($user -> ID, 'open_access_token', true);
	if($access_token){
		$params['access_token']=$access_token;
		if($method=='GET'){
			$result=open_connect_http($url.'?'.http_build_query($params));
		}else{
			$result=open_connect_http($url, http_build_query($params), 'POST');
		}
		return $result;	
	}
}
function open_connect_http($url, $postfields='', $method='GET', $headers=array()){
	$ci=curl_init();
	curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ci, CURLOPT_TIMEOUT, 30);
	if($method=='POST'){
		curl_setopt($ci, CURLOPT_POST, TRUE);
		if($postfields!='')curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
	}
	$headers[]='User-Agent: Open Social Login for China(xiaomac.com)';
	if(isset($_SESSION["access_token"])){
		$headers[]='Authorization: Bearer '.$_SESSION["access_token"];
	}
	curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ci, CURLOPT_URL, $url);
	$response=curl_exec($ci);
	curl_close($ci);
	$json_r=array();
	if($response!='')$json_r=json_decode($response, true);
	return $json_r;
}

//添加后台设置菜单
add_action('admin_menu', 'open_options_add_page');
function open_options_add_page() {
	add_options_page('Open Social Login', 'Open Social Login', 'manage_options', plugin_basename(__FILE__), 'open_options_page');
}

//插件列表设置入口
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'open_settings_link' );
function open_settings_link($links) {
	array_unshift($links, '<a href="options-general.php?page=open-social-login/open-social-login.php">Settings</a>');
	return $links;
}

//设置页面
function open_options_page() {
	global $path;
	if (isset($_POST['submit'])) {
		$cachefile = dirname(__FILE__) . '/setting.php';
		$fp = fopen($cachefile, 'w');
		$s = "<?php\n";
		$s .= "define('QQ_AKEY','".$_POST['QQ_AKEY']."');\n";
		$s .= "define('QQ_SKEY','".$_POST['QQ_SKEY']."');\n";
		$s .= "define('QQ_BACK','".$_POST['QQ_BACK']."');\n";
		$s .= "define('WB_AKEY','".$_POST['WB_AKEY']."');\n";
		$s .= "define('WB_SKEY','".$_POST['WB_SKEY']."');\n";
		$s .= "define('WB_BACK','".$_POST['WB_BACK']."');\n";
		$s .= "define('BD_AKEY','".$_POST['BD_AKEY']."');\n";
		$s .= "define('BD_SKEY','".$_POST['BD_SKEY']."');\n";
		$s .= "define('BD_BACK','".$_POST['BD_BACK']."');\n";
		$s .= "define('GG_AKEY','".$_POST['GG_AKEY']."');\n";
		$s .= "define('GG_SKEY','".$_POST['GG_SKEY']."');\n";
		$s .= "define('GG_BACK','".$_POST['GG_BACK']."');\n";
		$s .= "define('WL_AKEY','".$_POST['WL_AKEY']."');\n";
		$s .= "define('WL_SKEY','".$_POST['WL_SKEY']."');\n";
		$s .= "define('WL_BACK','".$_POST['WL_BACK']."');\n";
		$s .= "define('DB_AKEY','".$_POST['DB_AKEY']."');\n";
		$s .= "define('DB_SKEY','".$_POST['DB_SKEY']."');\n";
		$s .= "define('DB_BACK','".$_POST['DB_BACK']."');\n";
		$s .= "define('RR_AKEY','".$_POST['RR_AKEY']."');\n";
		$s .= "define('RR_SKEY','".$_POST['RR_SKEY']."');\n";
		$s .= "define('RR_BACK','".$_POST['RR_BACK']."');\n";
		$s .= "define('KX_AKEY','".$_POST['KX_AKEY']."');\n";
		$s .= "define('KX_SKEY','".$_POST['KX_SKEY']."');\n";
		$s .= "define('KX_BACK','".$_POST['KX_BACK']."');\n";
		$s .= "?>\n";
		fwrite($fp, $s);
		fclose($fp);
		echo "<div id='setting-error-settings_updated' class='updated settings-error'> <p><strong>QQ登录设置已保存。</strong></p><script>location.reload();</script></div>";
	} 
	echo '<div class="wrap">';
	echo '<div id="icon-options-general" class="icon32"><br /></div><h2>开放平台帐号设置</h2>';
	echo '<form method="post">';
	echo '<h3><a href="http://connect.qq.com/" target="_blank">[QQ] 腾讯QQ</a>';
	echo '（<a href="http://wiki.connect.qq.com/">开发文档</a>）</h3>';
	echo '<p>APP ID <input name="QQ_AKEY" value="' . QQ_AKEY . '" class="regular-text" /></p>';
	echo '<p>APP KEY <input name="QQ_SKEY" value="' . QQ_SKEY . '" class="regular-text" /></p>';
	echo '<p>回调地址 <input name="QQ_BACK" value="' . WB_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="http://open.weibo.com/" target="_blank">[SINA] 新浪微博</a>';
	echo '（<a href="http://open.weibo.com/wiki/">开发文档</a>）</h3>';
	echo '<p>App Key <input name="WB_AKEY" value="' . WB_AKEY . '" class="regular-text" /></p>';
	echo '<p>App Secret <input name="WB_SKEY" value="' . WB_SKEY . '" class="regular-text" /></p>';
	echo '<p>授权回调页 <input name="WB_BACK" value="' . WB_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="http://developer.baidu.com/console" target="_blank">[BAIDU] 百度</a>';
	echo '（<a target="_blank" href="http://developer.baidu.com/wiki/index.php?title=docs/oauth">开发文档</a>）</h3>';
	echo '<p>API Key <input name="BD_AKEY" value="' . BD_AKEY . '" class="regular-text" /></p>';
	echo '<p>Secret Key <input name="BD_SKEY" value="' . BD_SKEY . '" class="regular-text" /></p>';
	echo '<p>授权回调页 <input name="BD_BACK" value="' . BD_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="https://cloud.google.com/console" target="_blank">[GOOGLE] Google</a>';
	echo '（<a href="https://developers.google.com/accounts/docs/OAuth2WebServer">开发文档</a>）</h3>';
	echo '<p>CLIENT ID <input name="GG_AKEY" value="' . GG_AKEY . '" class="regular-text" /></p>';
	echo '<p>CLIENT SECRET <input name="GG_SKEY" value="' . GG_SKEY . '" class="regular-text" /></p>';
	if (strpos(GG_BACK, "http") !== false) {
		echo '<p>REDIRECT URI <input name="GG_BACK" value="' . GG_BACK . '" class="regular-text code" /> 注：测试发现不能带“?”，建议保留本默认值</p>';
	}else{
		echo '<p>REDIRECT URI <input name="GG_BACK" value="' . $path . '/google.php" class="regular-text code" /> 注：测试发现不能带“?”，故需要一层跳转，建议保留本默认值</p>';
	}
	echo '<h3><a href="https://account.live.com/developers/applications" target="_blank">[LIVE] 微软LIVE</a>';
	echo '（<a target="_blank" href="http://msdn.microsoft.com/en-us/library/live/ff621314.aspx">开发文档</a>）</h3>';
	echo '<p>Client ID <input name="WL_AKEY" value="' . WL_AKEY . '" class="regular-text" /></p>';
	echo '<p>Client secret <input name="WL_SKEY" value="' . WL_SKEY . '" class="regular-text" /></p>';
	echo '<p>Redirect domain <input name="WL_BACK" value="' . WL_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="http://developers.douban.com/" target="_blank">[DOUBAN] 豆瓣</a>';
	echo '（<a target="_blank" href="http://developers.douban.com/wiki/?title=oauth2">开发文档</a>）</h3>';
	echo '<p>API Key <input name="DB_AKEY" value="' . DB_AKEY . '" class="regular-text" /></p>';
	echo '<p>Secret <input name="DB_SKEY" value="' . DB_SKEY . '" class="regular-text" /></p>';
	echo '<p>回调地址 <input name="DB_BACK" value="' . DB_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="http://dev.renren.com/" target="_blank">[RENREN] 人人网</a>';
	echo '（<a target="_blank" href="http://wiki.dev.renren.com/wiki/Authentication">开发文档</a>）</h3>';
	echo '<p>APP KEY <input name="RR_AKEY" value="' . RR_AKEY . '" class="regular-text" /></p>';
	echo '<p>Secret Key <input name="RR_SKEY" value="' . RR_SKEY . '" class="regular-text" /></p>';
	echo '<p>回调地址 <input name="RR_BACK" value="' . RR_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<h3><a href="http://open.kaixin001.com/" target="_blank">[KAIXIN] 开心网</a>';
	echo '（<a target="_blank" href="http://open.kaixin001.com/document.php">开发文档</a>）</h3>';
	echo '<p>API Key <input name="KX_AKEY" value="' . KX_AKEY . '" class="regular-text" /></p>';
	echo '<p>Secret Key <input name="KX_SKEY" value="' . KX_SKEY . '" class="regular-text" /></p>';
	echo '<p>回调地址 <input name="KX_BACK" value="' . KX_BACK . '" class="regular-text code" /> 注：一般为首页 '.home_url('/').'</p>';
	echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="保存更改"  /></p>';
	echo '</form>';
	echo '</div>';
} 

//同步头像
add_filter("get_avatar", "open_get_avatar",10,4);
function open_get_avatar($avatar, $id_or_email='',$size='40') {
	global $comment;
	if(is_object($comment)) $id_or_email = $comment->user_id;
	if(is_object($id_or_email)) $id_or_email = $id_or_email->user_id;
	$open_type = get_user_meta($id_or_email, 'open_type', true);
	if ($open_type) {
		$open_id = get_user_meta($id_or_email, 'open_id', true);
		if($open_type=='qq'){
			$out = 'http://q.qlogo.cn/qqapp/100599436/'.$open_id.'/40';
		}elseif($open_type=='sina'){
			$out = 'http://tp3.sinaimg.cn/'.$open_id.'/50/1.jpg';
		}elseif($open_type=='baidu'){
			$out = 'http://himg.bdimg.com/sys/portraitn/item/'.$open_id.'.jpg';
		}elseif($open_type=='douban'){
			$out = 'http://img3.douban.com/icon/u'.$open_id.'.jpg';
		}elseif($open_type=='renren'||$open_type=='kaixin'){
			$out = get_user_meta($id_or_email, 'open_img', true);
		}
		if(isset($open_id) && isset($out)) $avatar = "<img alt='' src='{$out}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
	}
	return $avatar;
}

//登陆页面、评论表单登陆入口，请启用后先在后台配置帐号
//add_action('login_form', 'open_connect_login_form');
add_action('comment_form', 'open_connect_login_form');
function open_connect_login_form($login_type='guest') {
	global $path;
	if (!is_user_logged_in() || $login_type=='bind'){
		echo '<script>(function(){';
		echo 'function loginCode(id,txt){return "<div class=\'xmLoginIconSet icon_"+id+"\' onclick=\"window.open(\''.home_url('/').'?connect="+id+"&action=login\',\'xmOpenWindow\',\'width=550,height=400,menubar=0,scrollbars=1,resizable=1,status=1,titlebar=0,toolbar=0,location=1\');\" title=\'"+txt+"\'></div>";}';
		echo 'var loginHTML="<style>@import url('.$path.'/osl.css);</style>";';
		echo 'loginHTML+="<div class=xmLoginForm>";';
		if(QQ_AKEY) echo 'loginHTML+=loginCode("qq","使用QQ登陆");';
		if(WB_AKEY) echo 'loginHTML+=loginCode("sina","使用新浪微博登陆");';
		if(BD_AKEY) echo 'loginHTML+=loginCode("baidu","使用百度帐号登陆");';
		if(GG_AKEY) echo 'loginHTML+=loginCode("google","使用Google帐号登陆");';
		if(WL_AKEY) echo 'loginHTML+=loginCode("live","使用微软Live帐号登陆");';
		if(DB_AKEY) echo 'loginHTML+=loginCode("douban","使用豆瓣帐号登陆");';
		if(RR_AKEY) echo 'loginHTML+=loginCode("renren","使用人人网帐号登陆");';
		if(KX_AKEY) echo 'loginHTML+=loginCode("kaixin","使用开心网帐号登陆");';
		echo 'loginHTML+="</div>";';
		echo 'document.write(loginHTML);';
		echo '})();</script>';
	}
} 

//用户可从资料页选择绑定或取消，只有用户自己能操作
add_action('personal_options', 'open_connect_personal_options');
function open_connect_personal_options() {
	$user_id = get_current_user_id();
	if(isset($_GET['user_id']) && $user_id!=$_GET['user_id']) return;
	echo '<tr>';
	echo '<th scope="row"></th><td>';
	$open_type = get_user_meta($user_id, 'open_type', true);
	if ($open_type) {
		echo '<input class="button-primary" type="button" onclick=\'window.open("'.home_url('/').'?connect='.$open_type.'&action=unbind", "xmOpenWindow","width=500,height=350,menubar=0,scrollbars=1,resizable=1,status=1,titlebar=0,toolbar=0,location=0");return false;\' value="解除 '.strtoupper($open_type).' 的登陆绑定"/> ';
	} else {
		open_connect_login_form('bind');
	} 
	echo '</td></tr>';
} 

?>