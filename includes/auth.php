<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /user/login.php');
        exit;
    }
}

function isAdmin(): bool
{
    return isset($_SESSION['user_role']) &&
           $_SESSION['user_role'] === 'admin';
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}