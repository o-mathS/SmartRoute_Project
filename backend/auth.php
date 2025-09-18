<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../views/index.php");
    exit;
}

// Função helper pra checar role
function isAdmin() {
    return isset($_SESSION['usuario_role']) && $_SESSION['usuario_role'] === 'admin';
}
