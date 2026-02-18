<?php
/**
 * API endpoint para recibir fotos desde ESP32-CAM
 * URL: /api/stream.php
 * Método: POST
 * Parámetro: image (archivo binario de imagen)
 */

// Aumentar límites para recibir fotos grandes
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('memory_limit', '128M');
ini_set('max_execution_time', '60');

// Log de errores para debug
error_log("Stream.php accedido: " . date('Y-m-d H:i:s') . " - Método: " . $_SERVER['REQUEST_METHOD']);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Directorio donde se guardarán las fotos
$uploadDir = __DIR__ . '/../public/assets/stream/';

// Asegurar que el directorio existe
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Endpoint para recibir fotos desde ESP32-CAM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Verificar si se recibió data en el body (para ESP32-CAM)
    $imageData = file_get_contents('php://input');
    
    if (!empty($imageData)) {
        // Generar nombre único con timestamp
        $filename = 'stream_' . date('YmdHis') . '_' . uniqid() . '.jpg';
        $filepath = $uploadDir . $filename;
        
        // Guardar la imagen
        if (file_put_contents($filepath, $imageData)) {
            // Mantener solo las últimas 100 imágenes
            cleanOldImages($uploadDir, 100);
            
            // Actualizar el archivo de última imagen
            $latestFile = $uploadDir . 'latest.jpg';
            copy($filepath, $latestFile);
            
            echo json_encode([
                'success' => true,
                'message' => 'Imagen recibida correctamente',
                'filename' => $filename,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar la imagen'
            ]);
        }
    } 
    // También soportar upload tradicional de formulario (por si se usa para pruebas)
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $filename = 'stream_' . date('YmdHis') . '_' . uniqid() . '.jpg';
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($tmpName, $filepath)) {
            cleanOldImages($uploadDir, 100);
            
            $latestFile = $uploadDir . 'latest.jpg';
            copy($filepath, $latestFile);
            
            echo json_encode([
                'success' => true,
                'message' => 'Imagen recibida correctamente',
                'filename' => $filename,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar la imagen'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se recibió ninguna imagen'
        ]);
    }
}

// Endpoint para obtener la última imagen (GET)
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['action']) && $_GET['action'] === 'latest') {
        $latestFile = $uploadDir . 'latest.jpg';
        
        if (file_exists($latestFile)) {
            echo json_encode([
                'success' => true,
                'url' => '/public/assets/stream/latest.jpg',
                'timestamp' => filemtime($latestFile)
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No hay imágenes disponibles'
            ]);
        }
    }
    
    // Listar todas las imágenes
    elseif (isset($_GET['action']) && $_GET['action'] === 'list') {
        $images = glob($uploadDir . 'stream_*.jpg');
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($images, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $imageList = [];
        foreach ($images as $image) {
            $imageList[] = [
                'filename' => basename($image),
                'url' => '/public/assets/stream/' . basename($image),
                'timestamp' => date('Y-m-d H:i:s', filemtime($image)),
                'size' => filesize($image)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'count' => count($imageList),
            'images' => $imageList
        ]);
    }
    
    else {
        echo json_encode([
            'success' => true,
            'message' => 'ESP32-CAM Stream API',
            'endpoints' => [
                'POST /' => 'Enviar imagen (body raw con datos binarios)',
                'GET /?action=latest' => 'Obtener URL de la última imagen',
                'GET /?action=list' => 'Listar todas las imágenes'
            ]
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

/**
 * Función para limpiar imágenes antiguas
 */
function cleanOldImages($dir, $maxImages) {
    $images = glob($dir . 'stream_*.jpg');
    
    if (count($images) > $maxImages) {
        // Ordenar por fecha de modificación (más antiguas primero)
        usort($images, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Eliminar las imágenes más antiguas
        $toDelete = count($images) - $maxImages;
        for ($i = 0; $i < $toDelete; $i++) {
            if (file_exists($images[$i])) {
                unlink($images[$i]);
            }
        }
    }
}
