<?php
require_once('../config.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);
class Master extends DBConnection
{
	private $settings;
	public function __construct()
	{
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct()
	{
		parent::__destruct();
	}
	function capture_err()
	{
		if (!$this->conn->error)
			return false;
		else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_brand()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$v = addslashes(trim($v));
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `brands` where `name` = '{$name}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Brand Name already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `brands` set {$data} ";
		} else {
			$sql = "UPDATE `brands` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if (empty($id))
				$resp['msg'] = "New Brand successfully saved.";
			else
				$resp['msg'] = "Brand successfully updated.";
			if (!empty($_FILES['img']['tmp_name'])) {
				if (!is_dir(base_app . "uploads/brands")) {
					mkdir(base_app . "uploads/brands", 0777, true);
				}
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = "uploads/brands/$bid.$ext";
				$accept = array('image/jpeg', 'image/png');
				$uploaded = false;

				if (extension_loaded('gd') && in_array($_FILES['img']['type'], $accept)) {
					$uploadfile = false;
					if ($_FILES['img']['type'] == 'image/jpeg') {
						$uploadfile = @imagecreatefromjpeg($_FILES['img']['tmp_name']);
					} elseif ($_FILES['img']['type'] == 'image/png') {
						$uploadfile = @imagecreatefrompng($_FILES['img']['tmp_name']);
					}
					
					if ($uploadfile) {
						$temp = imagescale($uploadfile, 200, 200);
						if (is_file(base_app . $fname)) {
							unlink(base_app . $fname);
						}
						if ($_FILES['img']['type'] == 'image/jpeg') {
							$uploaded = imagejpeg($temp, base_app . $fname);
						} elseif ($_FILES['img']['type'] == 'image/png') {
							$uploaded = imagepng($temp, base_app . $fname);
						}
						imagedestroy($temp);
						imagedestroy($uploadfile);
					}
				}

				if (!$uploaded) {
					if (is_file(base_app . $fname)) {
						unlink(base_app . $fname);
					}
					$uploaded = move_uploaded_file($_FILES['img']['tmp_name'], base_app . $fname);
				}

				if ($uploaded) {
					$qry = $this->conn->query("UPDATE brands set `image_path` = CONCAT('{$fname}', '?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$bid}' ");
				} else {
					$resp['msg'] .= " Image upload failed.";
				}
			}
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		if ($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}
	function delete_brand()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `brands` set `delete_flag` = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Brand successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_category()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if (isset($_POST['description'])) {
			if (!empty($data)) $data .= ",";
			$data .= " `description`='" . addslashes(htmlentities($description)) . "' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where `category` = '{$category}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Category already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `categories` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "New Category successfully saved.");
			else
				$this->settings->set_flashdata('success', "Category successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category()
	{
		extract($_POST);
		$del = $this->conn->query("UPDATE `categories` set delete_flag = 1 where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Category successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_product()
	{
		$_POST['specs'] = htmlentities($_POST['specs']);
		foreach ($_POST as $k => $v) {
			$_POST[$k] = addslashes($v);
		}
		extract($_POST);

		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'stock', 'price'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}

		$check = $this->conn->query("SELECT * FROM products WHERE name='{$name}' " . (!empty($id) ? " AND id != {$id}" : ""))->num_rows;

		if ($check > 0) {
			return json_encode(['status' => 'failed', 'msg' => 'Product already exists']);
		}

		if (empty($id)) {
			$sql = "INSERT INTO products SET {$data}";
		} else {
			$sql = "UPDATE products SET {$data} WHERE id='{$id}'";
		}

		$save = $this->conn->query($sql);

		if ($save) {
			$pid = empty($id) ? $this->conn->insert_id : $id;
			$resp['status'] = 'success';
			$resp['id'] = $pid;

			if (isset($_FILES['img']) && count($_FILES['img']['tmp_name']) > 0) {
				$upload_path = base_app . "uploads/product_" . $pid;
				if (!is_dir($upload_path)) {
					mkdir($upload_path, 0777, true);
				}
				foreach ($_FILES['img']['tmp_name'] as $k => $v) {
					if (!empty($_FILES['img']['tmp_name'][$k])) {
						$fname = $_FILES['img']['name'][$k];
						$fname = preg_replace("/[^a-zA-Z0-9\._-]/", "", $fname);
						move_uploaded_file($_FILES['img']['tmp_name'][$k], $upload_path . '/' . $fname);
					}
				}
			}
		} else {
			$resp['status'] = 'failed';
		}

		return json_encode($resp);
	}
	function delete_product()
	{
		extract($_POST);

		$del = $this->conn->query("UPDATE products SET delete_flag = 1 WHERE id = '$id'");

		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Product successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}

		return json_encode($resp);
	}
	function delete_img()
	{
		extract($_POST);
		if (is_file($path)) {
			if (unlink($path)) {
				$resp['status'] = 'success';
			} else {
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete ' . $path;
			}
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown ' . $path . ' path';
		}
		return json_encode($resp);
	}
	function save_inventory()
	{
		extract($_POST);
		$data = "";
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id', 'description'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `inventory` where `product_id` = '{$product_id}' and variant = '{$variant}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Inventory already exist.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `inventory` set {$data} ";
			$save = $this->conn->query($sql);
		} else {
			$sql = "UPDATE `inventory` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if ($save) {
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "New Inventory successfully saved.");
			else
				$this->settings->set_flashdata('success', "Inventory successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_inventory()
	{
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `inventory` where id = '{$id}'");
		if ($del) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Invenory successfully deleted.");
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function register()
	{
		extract($_POST);
		$data = "";
		$_POST['password'] = md5($_POST['password']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `clients` where `email` = '{$email}' " . (!empty($id) ? " and id != {$id} " : "") . " ")->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		if (empty($id)) {
			$sql = "INSERT INTO `clients` set {$data} ";
		} else {
			$sql = "UPDATE `clients` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if ($save) {
			$cid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if (empty($id))
				$this->settings->set_flashdata('success', "Account successfully created.");
			else
				$this->settings->set_flashdata('success', "Account successfully updated.");
			$this->settings->set_userdata('login_type', 2);
			foreach ($_POST as $k => $v) {
				$this->settings->set_userdata($k, $v);
			}
			$this->settings->set_userdata('id', $cid);
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function add_to_cart()
	{
		extract($_POST);
		
		if (!isset($quantity) || (int)$quantity <= 0) {
			return json_encode([
				'status' => 'failed',
				'msg' => 'Please enter a valid quantity of 1 or more.'
			]);
		}

		$client_id = $this->settings->userdata('id');
		$data = " client_id = '" . $client_id . "' ";
		$_POST['price'] = str_replace(",", "", $_POST['price']);
		foreach ($_POST as $k => $v) {
			if (!in_array($k, array('id'))) {
				if (!empty($data)) $data .= ",";
				$data .= " `{$k}`='{$v}' ";
			}
		}

		// Calculate available stock
		$inv = $this->conn->query("SELECT quantity FROM inventory WHERE id='{$inventory_id}'")->fetch_assoc();
		$sold = $this->conn->query("SELECT IFNULL(SUM(quantity),0) as sold FROM order_list WHERE inventory_id='{$inventory_id}'")->fetch_assoc();
		$available = $inv['quantity'] - $sold['sold'];

		// Retrieve current quantity in cart (if any)
		$current_cart = $this->conn->query("SELECT quantity FROM `cart` WHERE `inventory_id` = '{$inventory_id}' and client_id = " . $client_id);
		$current_cart_qty = 0;
		if ($current_cart->num_rows > 0) {
			$current_cart_qty = (int)$current_cart->fetch_assoc()['quantity'];
		}

		$total_requested = $current_cart_qty + (int)$quantity;

		if ($total_requested > $available) {
			if ($available <= 0) {
				return json_encode([
					'status' => 'failed',
					'msg' => 'This product is out of stock.'
				]);
			}
			$remaining = $available - $current_cart_qty;
			if ($remaining > 0) {
				return json_encode([
					'status' => 'failed',
					'msg' => "Only {$remaining} more items can be added to your cart (You already have {$current_cart_qty} in cart)."
				]);
			} else {
				return json_encode([
					'status' => 'failed',
					'msg' => "Cannot add more. You already have all {$current_cart_qty} available items in your cart."
				]);
			}
		}

		$check = $current_cart->num_rows;
		if ($this->capture_err())
			return $this->capture_err();
		if ($check > 0) {
			$sql = "UPDATE `cart` set quantity = quantity + {$quantity} where `inventory_id` = '{$inventory_id}' and client_id = " . $client_id;
		} else {
			$sql = "INSERT INTO `cart` set {$data} ";
		}

		$save = $this->conn->query($sql);
		if ($this->capture_err())
			return $this->capture_err();
		if ($save) {
			$resp['status'] = 'success';
			$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) as items from `cart` where client_id =" . $client_id)->fetch_assoc()['items'];
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function update_cart_qty()
	{
		extract($_POST);

		if (!isset($quantity) || (int)$quantity < 1) {
			return json_encode([
				'status' => 'failed',
				'msg' => "Quantity must be at least 1."
			]);
		}

		$get = $this->conn->query("SELECT inventory_id FROM cart WHERE id='{$id}'");
		$row = $get->fetch_assoc();

		$inv = $this->conn->query("SELECT quantity FROM inventory WHERE id='{$row['inventory_id']}'")->fetch_assoc();

		$sold = $this->conn->query("SELECT IFNULL(SUM(quantity),0) as sold 
	FROM order_list 
	WHERE inventory_id='{$row['inventory_id']}'")->fetch_assoc();

		$available = $inv['quantity'] - $sold['sold'];

		if ($quantity > $available) {
			return json_encode([
				'status' => 'failed',
				'msg' => "Only {$available} items available"
			]);
		}

		$save = $this->conn->query("UPDATE `cart` set quantity = '{$quantity}' where id = '{$id}'");

		if ($save) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}

	function empty_cart()
	{
		$delete = $this->conn->query("DELETE FROM `cart` where client_id = " . $this->settings->userdata('id'));
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_cart()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `cart` where id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_order()
	{
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `orders` where id = '{$id}'");
		$delete2 = $this->conn->query("DELETE FROM `order_list` where order_id = '{$id}'");
		$delete3 = $this->conn->query("DELETE FROM `sales` where order_id = '{$id}'");
		if ($this->capture_err())
			return $this->capture_err();
		if ($delete) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', "Order successfully deleted");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error . "[{$sql}]";
		}
		return json_encode($resp);
	}
	function place_order()
	{
		extract($_POST);

		if (trim($delivery_address) == '') {
			return json_encode([
				'status' => 'failed',
				'msg' => 'Delivery address is required'
			]);
		}

		$client_id = $this->settings->userdata('id');

		$prefix = date("Ym");
		$code = sprintf("%'.05d", 1);

		while (true) {
			$check = $this->conn->query("SELECT * FROM `orders` WHERE ref_code='{$prefix}{$code}'")->num_rows;
			if ($check > 0) {
				$code = sprintf("%'.05d", (int)$code + 1);
			} else {
				break;
			}
		}

		$ref_code = $prefix . $code;

		$order_sql = "INSERT INTO `orders`
        SET client_id='{$client_id}',
            ref_code='{$ref_code}',
            payment_method='{$payment_method}',
            amount='{$amount}',
            paid='{$paid}',
            delivery_address='{$delivery_address}'";

		$save_order = $this->conn->query($order_sql);

		if (!$save_order) {
			return json_encode([
				'status' => 'failed',
				'msg' => $this->conn->error
			]);
		}

		$order_id = $this->conn->insert_id;

		$cart = $this->conn->query("
        SELECT c.*,p.name,i.price
        FROM `cart` c
        INNER JOIN `inventory` i ON i.id=c.inventory_id
        INNER JOIN `products` p ON p.id=i.product_id
        WHERE c.client_id='{$client_id}'
    ");

		$data = '';

		while ($row = $cart->fetch_assoc()) {

			$inv = $this->conn->query("
            SELECT quantity 
            FROM inventory 
            WHERE id='{$row['inventory_id']}'
        ")->fetch_assoc();

			$sold = $this->conn->query("
            SELECT IFNULL(SUM(quantity),0) as sold
            FROM order_list
            WHERE inventory_id='{$row['inventory_id']}'
        ")->fetch_assoc();

			$available = $inv['quantity'] - $sold['sold'];

			if ($row['quantity'] > $available) {
				return json_encode([
					'status' => 'failed',
					'msg' => "Insufficient stock for {$row['name']}"
				]);
			}

			if (!empty($data)) $data .= ",";

			$total = $row['price'] * $row['quantity'];

			$data .= "(
            '{$order_id}',
            '{$row['inventory_id']}',
            '{$row['quantity']}',
            '{$row['price']}',
            '{$total}'
        )";
		}

		$save_items = $this->conn->query("
        INSERT INTO order_list(order_id,inventory_id,quantity,price,total)
        VALUES {$data}
    ");

		if (!$save_items) {
			return json_encode([
				'status' => 'failed',
				'msg' => $this->conn->error
			]);
		}

		$this->conn->query("DELETE FROM cart WHERE client_id='{$client_id}'");

		$this->conn->query("
        INSERT INTO sales(order_id,total_amount)
        VALUES('{$order_id}','{$amount}')
    ");

		$this->settings->set_flashdata('success', "Order placed successfully.");

		return json_encode([
			'status' => 'success'
		]);
	}
	function update_order_status()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `status` = '$status' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("success", " Order status successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function pay_order()
	{
		extract($_POST);
		$update = $this->conn->query("UPDATE `orders` set `paid` = '1' where id = '{$id}' ");
		if ($update) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata("success", " Order payment status successfully updated.");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_account()
	{
		if (!empty($_POST['password']))
			$_POST['password'] = md5($_POST['password']);
		else
			unset($_POST['password']);
		extract($_POST);
		$data = "";
		if (md5($cpassword) != $this->settings->userdata('password')) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Current Password is Incorrect";
			return json_encode($resp);
			exit;
		}
		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach ($_POST as $k => $v) {
			if ($k == 'cpassword' || ($k == 'password' && empty($v)))
				continue;
			if (!empty($data)) $data .= ",";
			$data .= " `{$k}`='{$v}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if ($save) {
			foreach ($_POST as $k => $v) {
				if ($k != 'cpassword')
					$this->settings->set_userdata($k, $v);
			}

			$this->settings->set_userdata('id', $id);
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', ' Your Account Details has been updated successfully.');
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_client()
	{
		if (!empty($_POST['password']))
			$_POST['password'] = md5($_POST['password']);
		else
			unset($_POST['password']);
		extract($_POST);
		$data = "";

		$check = $this->conn->query("SELECT * FROM `clients`  where `email`='{$email}' and `id` != $id ")->num_rows;
		if ($check > 0) {
			$resp['status'] = 'failed';
			$resp['msg'] = "Email already taken.";
			return json_encode($resp);
			exit;
		}
		foreach ($_POST as $k => $v) {
			if (in_array($k, ['id']))
				continue;
			if (!empty($data)) $data .= ",";
			$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
		}
		$save = $this->conn->query("UPDATE `clients` set $data where id = $id ");
		if ($save) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', ' Client Details Successfully Updated.');
		} else {
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function delete_client()
	{
		extract($_POST);
		$delete = $this->conn->query("UPDATE `clients` set delete_flag = 1 where id = '{$id}'");
		if ($delete) {
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success', " Client successfully deleted");
		} else {
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_brand':
		echo $Master->save_brand();
		break;
	case 'delete_brand':
		echo $Master->delete_brand();
		break;
	case 'save_category':
		echo $Master->save_category();
		break;
	case 'delete_category':
		echo $Master->delete_category();
		break;
	case 'save_sub_category':
		echo $Master->save_sub_category();
		break;
	case 'delete_sub_category':
		echo $Master->delete_sub_category();
		break;
	case 'save_product':
		echo $Master->save_product();
		break;
	case 'delete_product':
		echo $Master->delete_product();
		break;

	case 'save_inventory':
		echo $Master->save_inventory();
		break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
		break;
	case 'register':
		echo $Master->register();
		break;
	case 'add_to_cart':
		echo $Master->add_to_cart();
		break;
	case 'update_cart_qty':
		echo $Master->update_cart_qty();
		break;
	case 'delete_cart':
		echo $Master->delete_cart();
		break;
	case 'empty_cart':
		echo $Master->empty_cart();
		break;
	case 'delete_img':
		echo $Master->delete_img();
		break;
	case 'place_order':
		echo $Master->place_order();
		break;
	case 'update_order_status':
		echo $Master->update_order_status();
		break;
	case 'pay_order':
		echo $Master->pay_order();
		break;
	case 'update_account':
		echo $Master->update_account();
		break;
	case 'update_client':
		echo $Master->update_client();
		break;
	case 'delete_order':
		echo $Master->delete_order();
		break;
	case 'delete_client':
		echo $Master->delete_client();
		break;
	default:
		// echo $sysset->index();
		break;
}
