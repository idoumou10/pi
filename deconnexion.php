<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utilisateurs.php';

logoutFull();
header('Location: index.php');
exit;
