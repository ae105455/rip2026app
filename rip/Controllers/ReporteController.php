<?php

require_once __DIR__ . '/../Models/ReporteModel.php';
require_once __DIR__ . '/../fpdf.php';


class PDF extends FPDF
{
    function Header()
    {
        $rootPath = dirname(__DIR__);
        $logoPath = $rootPath . '/public/assets/logo.jpg';

        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 8, 36);
        }

        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 10, utf8_decode('REPORTE DETALLADO DE VENTAS'), 0, 1, 'C');

        $this->SetFont('Arial', 'I', 11);
        $this->Cell(0, 6, utf8_decode('Sistema de Control de Inventario y Ventas'), 0, 1, 'C');

        $this->Ln(6);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}


// Procesar petición
if (isset($_GET['action']) && $_GET['action'] === 'generar') {

    $previewMode = isset($_GET['preview']) && $_GET['preview'] === '1';

    if (!$previewMode) {
        // Validar método y parámetros
        if (
            $_SERVER['REQUEST_METHOD'] !== 'POST' ||
            !isset($_POST['fecha_inicio']) ||
            !isset($_POST['fecha_fin']) ||
            empty($_POST['fecha_inicio']) ||
            empty($_POST['fecha_fin'])
        ) {
            header('Location: ../index.php');
            exit;
        }

        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
    } else {
        $fecha_inicio = $_GET['fecha_inicio'] ?? '2026-03-01';
        $fecha_fin = $_GET['fecha_fin'] ?? '2026-03-01';
    }

    $model = new ReporteModel();
    $datos = $model->getVentasPorFecha($fecha_inicio, $fecha_fin);

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Rango consultado
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 6, "Rango consultado: $fecha_inicio al $fecha_fin", 0, 1, 'L');
    $pdf->Ln(2);

    // Encabezados
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetDrawColor(60, 60, 60);
    $pdf->SetLineWidth(0.1);
    $pdf->SetFillColor(230, 230, 230);

    $pdf->Cell(25, 8, 'Venta ID', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(65, 8, 'Producto', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Cant.', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'P. Unit.', 1, 0, 'R', true);
    $pdf->Cell(25, 8, 'Subtotal', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);

    $id_venta_actual = null;
    $total_venta = 0.0;
    $gran_total_periodo = 0.0;

    if (empty($datos)) {
        // Si se solicita preview (param preview=1) mostrar datos de ejemplo
        if ($previewMode) {
            $datos = [
                ['venta_id' => 1, 'fecha' => '2026-03-01', 'producto' => 'Monitor Gamer Asus 24"', 'cantidad' => 1, 'precio_unitario' => 189.50, 'subtotal' => 189.50],
                ['venta_id' => 1, 'fecha' => '2026-03-01', 'producto' => 'Teclado Mecánico RGB Logitech', 'cantidad' => 1, 'precio_unitario' => 85.00, 'subtotal' => 85.00],
                ['venta_id' => 1, 'fecha' => '2026-03-01', 'producto' => 'Laptop HP Pavilion 15"', 'cantidad' => 1, 'precio_unitario' => 749.99, 'subtotal' => 749.99],
                ['venta_id' => 1, 'fecha' => '2026-03-01', 'producto' => 'Mouse Inalámbrico Ergonomico', 'cantidad' => 1, 'precio_unitario' => 45.00, 'subtotal' => 45.00],
            ];
        } else {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 10, utf8_decode('No se encontraron ventas en el rango seleccionado.'), 0, 1, 'C');
            $pdf->Ln(5);
        }
    }
    if (empty($datos)) {
        // Si se solicita preview (param preview=1) mostrar datos de ejemplo
        if ($previewMode) {
            $datos = [
                ['venta_id' => 2, 'fecha' => '2026-03-01', 'producto' => 'Monitor Gamer Asus 24"', 'cantidad' => 2, 'precio_unitario' => 189.50, 'subtotal' => 189.50],
                ['venta_id' => 2, 'fecha' => '2026-03-01', 'producto' => 'Teclado Mecánico RGB Logitech', 'cantidad' => 2, 'precio_unitario' => 85.00, 'subtotal' => 85.00],
                ['venta_id' => 2, 'fecha' => '2026-03-01', 'producto' => 'Laptop HP Pavilion 15"', 'cantidad' => 2, 'precio_unitario' => 749.99, 'subtotal' => 749.99],
                ['venta_id' => 2, 'fecha' => '2026-03-01', 'producto' => 'Mouse Inalámbrico Ergonomico', 'cantidad' => 2, 'precio_unitario' => 45.00, 'subtotal' => 45.00],
            ];
        } else {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 10, utf8_decode('No se encontraron ventas en el rango seleccionado.'), 0, 1, 'C');
            $pdf->Ln(5);
        }
    }


    // Si ahora $datos contiene filas (reales o de preview), procesarlas
    if (!empty($datos)) {
        foreach ($datos as $fila) {
            // Si cambiamos de venta, imprimimos el total acumulado de la anterior
            if ($id_venta_actual !== null && $id_venta_actual != $fila['venta_id']) {
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(165, 8, 'TOTAL VENTA # ' . $id_venta_actual . ':', 1, 0, 'R', true);
                $pdf->Cell(25, 8, '$us ' . number_format($total_venta, 2), 1, 1, 'R', true);
                $pdf->SetFont('Arial', '', 9);
                $pdf->Ln(2);
                $total_venta = 0.0;
            }

            // Detectar primera línea de la venta para mostrar Venta ID
            $is_first_line_of_sale = ($id_venta_actual !== $fila['venta_id']);
            if ($is_first_line_of_sale) {
                $id_venta_actual = $fila['venta_id'];
            }

            $total_venta += $fila['subtotal'];
            $gran_total_periodo += $fila['subtotal'];

            $pdf->Cell(25, 8, $is_first_line_of_sale ? $fila['venta_id'] : '', 1, 0, 'C');
            $pdf->Cell(30, 8, $fila['fecha'], 1, 0, 'C');
            $pdf->Cell(65, 8, utf8_decode($fila['producto']), 1, 0, 'L');
            $pdf->Cell(20, 8, $fila['cantidad'], 1, 0, 'C');
            $pdf->Cell(25, 8, '$us ' . number_format($fila['precio_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(25, 8, '$us ' . number_format($fila['subtotal'], 2), 1, 1, 'R');
        }

        // Imprimir total de la última venta
        if ($id_venta_actual !== null) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(165, 8, 'TOTAL VENTA # ' . $id_venta_actual . ':', 1, 0, 'R', true);
            $pdf->Cell(25, 8, '$us ' . number_format($total_venta, 2), 1, 1, 'R', true);
        }

        $pdf->Ln(5);
    }

    // Gran total del periodo — banda azul de ancho completo
    $pdf->SetDrawColor(60, 60, 60);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->SetFont('Arial', 'B', 11);

    // calcular ancho util de la página (entre márgenes). Usamos GetPageWidth y GetX()
    // Asumimos márgenes simétricos; ancho útil = ancho pagina - 2*leftMargin
    $pageWidth = $pdf->GetPageWidth() - 2 * $pdf->GetX();
    $bandHeight = 12;
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // dibujar rectángulo relleno como fondo
    $pdf->Rect($x, $y, $pageWidth, $bandHeight, 'F');

    // dibujar las celdas encima (bordes visibles)
    $pdf->SetXY($x, $y);
    $pdf->Cell($pageWidth - 30, $bandHeight, 'TOTAL GENERAL DEL PERIODO:', 1, 0, 'R', false);
    $pdf->Cell(30, $bandHeight, '$us ' . number_format($gran_total_periodo, 2), 1, 1, 'R', false);

    // Enviar PDF al navegador
    $pdf->Output('I', 'reporte_ventas.pdf');
    exit;
}

?>
