<?php
session_start();

// Función de redirección simple
function redirect($page){
    header('location: ' . URLROOT . '/' . $page);
    exit();
}

// Verificar si el usuario está logueado
function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

// Verificar si el usuario tiene un rol específico
function hasRole($role){
    if(isLoggedIn() && $_SESSION['user_role'] == $role){
        return true;
    }
    return false;
}
