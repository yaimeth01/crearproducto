<?php
require_once __DIR__ . "/conexion.php";

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? "");
    $descripcion = trim($_POST['descripcion'] ?? "");
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $precio_compra = floatval($_POST['precio_compra'] ?? 0);
    $precio_venta = floatval($_POST['precio_venta'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $stock_maximo = intval($_POST['stock_maximo'] ?? 0);
    $unidad_medida = trim($_POST['unidad_medida'] ?? "");
    $nit_proveedor = trim($_POST['nit_proveedor'] ?? "");
    $nombre_proveedor = trim($_POST['nombre_proveedor'] ?? "");

    $errores = [];
    if ($id_categoria <= 0) $errores[] = "Seleccione una categoría válida.";
    if ($nombre === "") $errores[] = "El nombre es obligatorio.";
    if ($descripcion === "") $errores[] = "La descripción es obligatoria.";
    if ($cantidad < 0) $errores[] = "La cantidad no puede ser negativa.";
    if ($precio_compra < 0) $errores[] = "El precio de compra no puede ser negativo.";
    if ($precio_venta <= $precio_compra) $errores[] = "El precio de venta debe ser mayor que el precio de compra.";
    if ($stock_minimo < 0) $errores[] = "El stock mínimo no puede ser negativo.";
    if ($stock_maximo < 0) $errores[] = "El stock máximo no puede ser negativo.";
    if ($stock_minimo >= $stock_maximo) $errores[] = "El stock mínimo debe ser menor que el máximo.";
    if ($unidad_medida === "") $errores[] = "Seleccione una unidad de medida.";
    if ($nit_proveedor === "" || !preg_match('/^[0-9]+$/', $nit_proveedor)) $errores[] = "El NIT debe ser numérico.";
    if ($nombre_proveedor === "") $errores[] = "El nombre del proveedor es obligatorio.";

    // Imagen
    $imagenPath = null;
    if (!empty($_FILES['imagen']['name'])) {
        $allowed = ['jpg','jpeg','png','gif'];
        $fileName = basename($_FILES['imagen']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errores[] = "Formato de imagen no permitido (jpg, png, gif).";
        } else {
            $targetDir = __DIR__ . "/imagenes/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
            $newName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/','_', $fileName);
            $targetFile = $targetDir . $newName;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
                $imagenPath = "imagenes/" . $newName;
            } else {
                $errores[] = "No se pudo subir la imagen.";
            }
        }
    }

    // Verificar duplicado
    if (empty($errores)) {
        $nombre_norm = mb_strtolower(trim($nombre), 'UTF-8');
        $sql_check = "SELECT COUNT(*) FROM Productos WHERE LOWER(TRIM(nombre)) = ?";
        $check = $conn->prepare($sql_check);
        $check->bind_param("s", $nombre_norm);
        $check->execute();
        $check->store_result();
        $check->bind_result($count);
        $check->fetch();
        if ($count > 0) $errores[] = "Ya existe un producto con ese nombre.";
        $check->close();
    }

    if (empty($errores)) {
        $sql = "INSERT INTO Productos (id_categoria, nombre, descripcion, imagen, cantidad, precio_compra, precio_venta, stock_minimo, stock_maximo, unidad_medida, nit_proveedor, nombre_proveedor)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssiddiisss", $id_categoria, $nombre, $descripcion, $imagenPath, $cantidad, $precio_compra, $precio_venta, $stock_minimo, $stock_maximo, $unidad_medida, $nit_proveedor, $nombre_proveedor);
        if ($stmt->execute()) {
            $mensaje = "✅ Producto registrado correctamente.";
        } else {
            $mensaje = "Error al guardar: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = "❌ Errores:<br>" . implode("<br>", $errores);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Producto</title>
  <link rel="stylesheet" href="stilo.css">
</head>
<body>
  <div class="alerts" id="alerts"></div>
  <div class="card">
    <h2>Registrar Nuevo Producto</h2>

    <?php if ($mensaje): ?>
      <script>
        window.addEventListener('DOMContentLoaded', () => {
          const box = document.getElementById('alerts');
          const div = document.createElement('div');
          const isError = <?php echo json_encode(stripos($mensaje, 'error') !== false || stripos($mensaje, '❌') !== false); ?>;
          div.className = 'alert ' + (isError ? 'error' : 'success');
          div.innerHTML = `<span><?php echo $mensaje; ?></span><button class="close">×</button>`;
          box.appendChild(div);
          div.querySelector('.close').addEventListener('click',()=>div.remove());
          setTimeout(()=>div.remove(),4000);
        });
      </script>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Categoría:</label>
      <select name="id_categoria" required>
        <option value="">Seleccione...</option>
        <option value="1">Bebidas alcohólicas</option>
        <option value="2">Refrescos</option>
        <option value="3">Snacks</option>
      </select>

      <label>Nombre:</label>
      <input type="text" name="nombre" required>

      <label>Descripción:</label>
      <textarea name="descripcion" rows="3" required></textarea>

      <label>Imagen (opcional):</label>
      <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.gif">

      <label>Cantidad:</label>
      <input type="number" name="cantidad" min="0" required>

      <label>Precio compra:</label>
      <input type="number" name="precio_compra" step="0.01" min="0" required>

      <label>Precio venta:</label>
      <input type="number" name="precio_venta" step="0.01" min="0" required>

      <label>Stock mínimo:</label>
      <input type="number" name="stock_minimo" min="0" required>

      <label>Stock máximo:</label>
      <input type="number" name="stock_maximo" min="0" required>

      <label>Unidad de medida:</label>
      <select name="unidad_medida" required>
        <option value="">Seleccione...</option>
        <option value="Unidad">Unidad</option>
        <option value="Caja">Caja</option>
        <option value="Litro">Litro</option>
        <option value="Botella">Botella</option>
      </select>

      <label>NIT Proveedor:</label>
      <input type="text" name="nit_proveedor" required>

      <label>Nombre Proveedor:</label>
      <input type="text" name="nombre_proveedor" required>

      <div class="form-actions">
        <button type="submit">💾 Guardar producto</button>
        <a href="consultar_productos.php" class="btn-ver">📋 Ver Productos</a>
      </div>
    </form>
  </div>
</body>
</html>