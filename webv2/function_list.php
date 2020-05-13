<?php
error_reporting(E_ALL & ~E_NOTICE);

#-----------------------------------------------------------
# フォーム受け取り
#-----------------------------------------------------------
function parse_form(){
	global $in;

	$param = array();
	if (isset($_GET) && is_array($_GET)) { $param += $_GET; }
	if (isset($_POST) && is_array($_POST)) { $param += $_POST; }
	
	foreach($param as $key => $val) {
		# 2次元配列から値を取り出す
		if(is_array($val)){
			$val = array_shift($val);
		}
		
		# 文字コードの処理
		$enc = mb_detect_encoding($val);
		$val = mb_convert_encoding($val,"UTF-8",$enc);
		
		# 特殊文字の処理
		$val = htmlentities($val,ENT_QUOTES, "UTF-8");

		$in[$key] = $val;
	}
	if(isset($_GET["category_name"])){$in["category_name"] = $_GET["category_name"];}
	if(isset($_POST["category_name"])){$in["category_name"] = $_POST["category_name"];}

	return $in;
}

#-----------------------------------------------------------
# カテゴリの一覧を取得、ulとliで並べる
#-----------------------------------------------------------
function category_list(){
	global $db;
	global $tmpl_dir;

	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	# SQLを作成
	$query = "SELECT * FROM item_category";

	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->execute();

	$category_data = "<ul>";
	while($row = $stmt->fetch()){
		$category_name = $row['category_name'];
		$category_data .= "<li><input type='submit' name='category_name' value='$category_name'></li>";
    }
    $category_data .= "</ul>\n";

    return $category_data;
}

function pulldown_list($type){
	global $db;
	global $tmpl_dir;

	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	# SQLを作成
	if($type==1){
		$query = "SELECT * FROM item_category";
		$get_list="<select name='category_id'>";
		$want_name='category_name';
		$want_id='category_id';
	}else if($type==2){
		$query = "SELECT * FROM item_color";
		$get_list="<select name='color_id'>";
		$want_name='color_name';
		$want_id='color_id';
	}else{return -1;}


	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->execute();

	while($row = $stmt->fetch()){
		$get_list .= "<option value='$row[$want_id]'>$row[$want_name]</option>";
    }
    $get_list .= "</select>\n";

    return $get_list;
}

#-----------------------------------------------------------
# 読み込み
#-----------------------------------------------------------
function page_read($page){
	global $tmpl_dir;
	
	# テンプレート読み込み
	$conf = fopen( "$tmpl_dir/{$page}.tmpl", "r") or die;
	$size = filesize("$tmpl_dir/{$page}.tmpl");
	$tmpl = fread($conf, $size);
	fclose($conf);
	
	return $tmpl;
}

#-----------------------------------------------------------
# 名前からデータを受け取る
#-----------------------------------------------------------
function get_data_from_name($name,$type){
	global $in;
	global $db;

	if($type==1){$query = "SELECT * FROM item_category WHERE  category_name = :want_name";}
	else if($type==2){$query = "SELECT * FROM item_color WHERE  color_name = :want_name";}
	else if($type==3){$query = "SELECT * FROM item_data WHERE  item_name = :want_name";}
	else{return -1;}

	$stmt = $db->prepare($query);
	$stmt->bindParam(':want_name', $name);
	$stmt->execute();
	$row = $stmt->fetch();
	return $row;
}

#-----------------------------------------------------------
# idからデータを受け取る
#-----------------------------------------------------------
function get_data_from_id($id,$type){
	global $in;
	global $db;

	if($type==1){$query = "SELECT * FROM item_category WHERE category_id = :id";}
	else if($type==2){$query = "SELECT * FROM item_color WHERE color_id = :id";}
	else if($type==3){$query = "SELECT * FROM item_data WHERE item_id = :id";}
	else{return -1;}

	$stmt = $db->prepare($query);
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$row = $stmt->fetch();
	return $row;
}