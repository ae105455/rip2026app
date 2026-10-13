<?php

// Este archivo recibe todas las peticiones del sistema

// Si el usuario presiona el botón de generar,
// llamamos al controlador
if (
    isset($_GET['action']) &&
    $_GET['action'] == 'generar'
) {

    require_once 'Controllers/ReporteController.php';

} else {

    // Por defecto, mostramos la vista del formulario
    require_once 'Views/FormualrioReporte.php';
}
?>