<?php
/** LOGOUT (customers and admins). POST only, so another website cannot log people out with a hidden image. */
require_once __DIR__ . '/../app/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_verify();
logout_user();
session_start();            // fresh session just to show a message
flash_set('info', 'Anda telah logout.');
redirect('index.php');
