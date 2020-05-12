<?php
require "function_list.php";
error_reporting(E_ALL & ~E_NOTICE);

#データベース情報
$testuser ="testuser";
$testpass ="testpass";
$host ="localhost";
$datebase ="shop_item_v2";
# テンプレートディレクトリ
$tmpl_dir = "./tmpl";

try {
	$db = new PDO("mysql:host={$host}; dbname={$datebase}; charset=utf8", $testuser, $testpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	print_top();
}catch (PDOException $e) {
	die ("PDO Error:" . $e->getMessage());
}

function print_top(){
    $category_data =category_list();
	# 掲示板テンプレート読み込み
	$tmpl = page_read("top");
	# 文字変換
	$tmpl = str_replace("!category_data!",$category_data,$tmpl);
	
	echo $tmpl;
    exit;
}
