<?php
$i = $_GET['i'] ?? 0;
if ($i > 0) {
    header('location: /redirect.php?i=' . ($i - 1));
    exit;
}
header('X-Test: foo');
echo 'hello redirect';