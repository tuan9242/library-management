<?php
require_once __DIR__ . '/../config/database.php';

function handle_logout(): void {
    session_destroy();
    redirect('index.php?page=login');
}
?>

