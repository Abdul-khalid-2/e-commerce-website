<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name']);

header('Location: login.php');
exit;
