<?php
    require_once '../../config/conf.php';
    header("Content-Type: text/html;charset=UTF-8");
	
	if(isset($_SERVER["REMOTE_ADDR"])) $ipaddr = $_SERVER["REMOTE_ADDR"];
	
	if(empty($ipaddr) || empty($_POST['token']))) {
		echo "{\"result\":\"error\",\"error_message\":\"No access token.\"}";
	} else {
		$encodedText = $_POST['token'];
		$encodedText = base64_decode($encodedText);
		$user_id = openssl_decrypt($encodedText, 'AES-256-CBC', $db->getCipherKey(), true, $db->getIv());
		$auth = explode('|', $user_id, 2);
		
		if(count($auth) !== 2 || empty($auth[1]) || "null" === $auth[1]) {
			echo "{\"result\":\"error\",\"error_message\":\"Invalid access token.\"}";
			return;
		}
		
		$db = new DBC; 
		$db->db_conn();
		
		$sql = "SELECT COUNT(1) FROM login_history WHERE user_id=? AND ipaddr!=? AND logout!='Y' ";
		$stmt = $db->conn->prepare($sql);
		if($stmt 
			&& $stmt->bind_param("ss", $auth[1], $ipaddr) 
			&& $stmt->execute()
			&& $stmt->store_result() 
			&& $stmt->bind_result($login_count)
		) {
			if($stmt->fetch()) {
				echo "{\"result\":\"success\",\"login_count\":".$login_count."}";
			}
		} else {
			echo "{\"result\":\"error\",\"error_message\":\"SQL Exception.\"}";
		}
		
		$stmt->close();
		$db->db_close();
	}
?>