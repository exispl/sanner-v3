<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__.'/config.php';

function table_init() {
  $sql = "CREATE TABLE IF NOT EXISTS kv_store (
    k VARCHAR(128) PRIMARY KEY,
    v MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  pdo_conn()->exec($sql);
}

// PHP 5.x compatible - START
$action = 'get';
if (isset($_GET['a'])) {
    $action = $_GET['a'];
} else if (isset($_POST['a'])) {
    $action = $_POST['a'];
}

$key = 'KN_SHARED_V1';
if (isset($_GET['key'])) {
    $key = $_GET['key'];
} else if (isset($_POST['key'])) {
    $key = $_POST['key'];
}
// PHP 5.x compatible - END

try {
  table_init();

  if ($action === 'set') {
    $raw = file_get_contents('php://input');
    if (isset($_POST['v'])) { $raw = $_POST['v']; }
    $stmt = pdo_conn()->prepare("REPLACE INTO kv_store (k,v) VALUES (?,?)");
    $stmt->execute(array($key, $raw));
    echo json_encode(array('ok'=>true)); exit;
  }

  $stmt = pdo_conn()->prepare("SELECT v FROM kv_store WHERE k=?");
  $stmt->execute(array($key));
  $row = $stmt->fetch();

  // PHP 5.x compatible - START
  $value = null;
  if (isset($row['v'])) {
      $value = $row['v'];
  }
  echo json_encode(array('ok'=>true,'v'=>$value));
  // PHP 5.x compatible - END

} catch (Exception $e) { // Changed from Throwable for PHP 5.x compatibility
  http_response_code(500);
  echo json_encode(array('ok'=>false,'error'=>$e->getMessage()));
}
?>
