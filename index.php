<?php
// Configuration
$displayIp = '192.168.1.97';
$sendUrl = "http://$displayIp/send?adr=01&msg=";
$readUrl = "http://$displayIp/receive";

function readDisplay($url) {
    $result = @file_get_contents($url);
    return $result !== false ? trim($result) : '';
}

function sendMessage($url, $message) {
    $message = substr($message, 0, 15);
    $message = str_pad($message, 15);
    $encoded = urlencode($message);
    @file_get_contents($url . $encoded);
    usleep(150000); // wait 150ms
}

function buildStatusMessage() {
    $months = ['JAN','FEB','MAR','APR','MAI','JUN','JUL','AUG','SEP','OKT','NOV','DEZ'];
    $day = date('j');
    $month = $months[(int)date('n') - 1];

    $json = @file_get_contents('http://192.168.1.105/rpc/Shelly.GetStatus');
    if ($json !== false) {
        $data = json_decode($json, true);
        if (isset($data['temperature:100']['tC']) && isset($data['temperature:101']['tC'])) {
            $watertemp = round($data['temperature:100']['tC']);
            $outdoortemp = round($data['temperature:101']['tC']);
            return "$day $month $watertemp $outdoortemp";
        }
    }
    return "$day $month -- --";
}

$current = readDisplay($readUrl);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    sendMessage($sendUrl, $_POST['message']);
    $current = readDisplay($readUrl);
} elseif (isset($_GET['sendstatus'])) {
    $statusMessage = buildStatusMessage();
    sendMessage($sendUrl, $statusMessage);
    $current = readDisplay($readUrl);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Fallblattanzeige</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/milligram/dist/milligram.min.css">
</head>
<body>
<div class="container">
  <h3>Aktuelle Anzeige</h3>
  <pre><?php echo htmlspecialchars($current); ?></pre>

  <form method="post">
    <label for="message">Neue Nachricht (max 15 Zeichen)</label>
    <input type="text" id="message" name="message" maxlength="15">
    <button type="submit">Senden</button>
    <a class="button" href="?sendstatus=1">Status</a>
  </form>
</div>
</body>
</html>
