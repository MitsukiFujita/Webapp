<?php
error_reporting(E_ALL & ~E_NOTICE);
require "function_list.php";

#-----------------------------------------------------------
# 基本設定
#-----------------------------------------------------------
#データベース情報
$testuser ="testuser";
$testpass ="testpass";
$host ="localhost";
$datebase ="shop_item_v2";

# テンプレートディレクトリ
$tmpl_dir = "./tmpl";

#-----------------------------------------------------------
# ページの表示
#-----------------------------------------------------------
parse_form();

try {
	$db = new PDO("mysql:host={$host}; dbname={$datebase}; charset=utf8", $testuser, $testpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	if($in["state"] == "buy"){ 
		$in["item_increase"] =$in["item_increase"]*-1;
		$row = get_data_from_name($in['item_name'],3);
		$in["item_id"] = $row["item_id"];
		stock_update(); }
		item_search_customer();
}catch (PDOException $e) {
	die ("PDO Error:" . $e->getMessage());
}

#-----------------------------------------------------------
# 商品一覧
#-----------------------------------------------------------

function item_search_customer(){
	global $in;
	global $db;
	global $tmpl_dir;


	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	$row = get_data_from_name($in['category_name'],1);
	$category_id = $row["category_id"];

	# SQLを作成
	$query = "SELECT * FROM item_data WHERE item_flag = 1 and category_id = :category_id order by item_name";
	
	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->bindParam(':category_id', $category_id);
	$stmt->execute();

	$item_increase = pulldown_list(0,-1,10,"item_increase");

	$item_data = "";
	$item_name ="";
	while($row = $stmt->fetch()){
		if ($item_name == $row["item_name"]){continue;}
		$item_name = $row["item_name"];

		$color_list="<select name='color_id'>";
		$stock_list="<select name='item_stock'>";
		$price_list="<select name='item_price'>";

		$query2 = "SELECT * FROM item_data WHERE ( item_name = :want_name AND category_id = :category_id ) and item_flag = 1";	
		$stmt2 = $db->prepare($query2);
		$stmt2->bindParam(':want_name', $item_name);
		$stmt2->bindParam(':category_id', $category_id);
		$stmt2->execute();

		$i = 1;
		while($row2 = $stmt2->fetch()){
			if( $row2['item_stock'] == 0 ){$item_stock = "sold out";}
			else{$item_stock = $row2['item_stock']."個";}
			$stock_list .= "<option value='$item_stock'>$i:$item_stock</option>";
			$price_list .= "<option value='$row2[item_price]'>$i:$row2[item_price] 円</option>";

			$row3 = get_data_from_id($row2["color_id"],2);
			$color_list.= "<option value='$row2[color_id]'>$i:$row3[color_name]</option>";
			$i ++;
		}
		$color_list .= "</select>";
		$stock_list .= "</select>";
		$price_list .= "</select>";

		$item_data .= "<tr>";
		$item_data .= "<form action='customer.php' method='post'>";
		$item_data .= "<td class=\"form-left\">$in[category_name]</td>";
		$item_data .= "<td class=\"form-left\">$item_name</td>";
		$item_data .= "<td class=\"form-left\">$color_list</td>";
		$item_data .= "<td class=\"form-left\">$price_list</td>";
		$item_data .= "<td class=\"form-left\">$stock_list</td>";
		$item_data .= "<td class=\"form-left\">$item_increase 個</td>";
		$item_data .= "<td class=\"form-left\"><input type='submit' value='購入'></td>";
		$item_data .= "<input type='hidden' name='category_name' value='$in[category_name]'>";
		$item_data .= "<input type='hidden' name='category_id' value='$category_id'>";
		$item_data .= "<input type='hidden' name='item_id' value='$row[item_id]'>";
		$item_data .= "<input type='hidden' name='item_name' value='$row[item_name]'>";
		$item_data .= "<input type='hidden' name='state' value='buy'>";
		$item_data .= "</form>";
		$item_data .= "</tr>\n";
	}
	# 掲示板テンプレート読み込み
	$tmpl = page_read("list");
	$category_data =category_list();

	# 文字変換
	$tmpl = str_replace("!item_data!",$item_data,$tmpl);
	$tmpl = str_replace("!category_name!",$in['category_name'],$tmpl);
	$tmpl = str_replace("!category_data!",$category_data,$tmpl);
	
	echo $tmpl;
	exit;

}


