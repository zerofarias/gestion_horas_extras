<?php
// /gestion_horas_extras/generar_hash.php

// Muestra todos los errores para depuración.
ini_set('display_errors', 1);
error_reporting(E_ALL);

$hash_generado = '';
$password_ingresada = '';

// Si se envió el formulario...
if (isset($_POST['password'])) {
    $password_ingresada = $_POST['password'];
    // Generamos el hash usando el algoritmo por defecto de tu PHP.
    $hash_generado = password_hash($password_ingresada, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Hash de Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .container { max-width: 600px; }
        .result-box {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: monospace;
            word-wrap: break-word;
            border: 1px solid #ced4da;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Generador de Hash (Compatible con tu PHP)</h2>
            </div>
            <div class="card-body">
                <p>Usa este formulario para crear un hash de contraseña que sea 100% compatible con tu versión de PHP.</p>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña en texto plano:</label>
                        <input type="text" name="password" class="form-control" id="password" value="<?php echo htmlspecialchars($password_ingresada); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Generar Hash</button>
                </form>

                <?php if ($hash_generado): ?>
                    <hr>
                    <h4 class="mt-4">Resultado:</h4>
                    <p>Para la contraseña "<strong><?php echo htmlspecialchars($password_ingresada); ?></strong>", el hash generado es:</p>
                    <div class="result-box bg-success text-white">
                        <?php echo $hash_generado; ?>
                    </div>
                    <p class="mt-2 text-muted">Copia este hash completo y úsalo para actualizar la base de datos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
