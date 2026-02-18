<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Galería ESP32-CAM'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            font-size: 2em;
            color: #333;
        }
        
        .header-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .photo-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .photo-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .photo-card img {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            display: block;
        }
        
        .photo-info {
            padding: 15px;
        }
        
        .photo-info .timestamp {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
        }
        
        .photo-info .details {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            color: #999;
        }
        
        .photo-actions {
            display: flex;
            gap: 10px;
            padding: 0 15px 15px;
        }
        
        .photo-actions button {
            flex: 1;
            padding: 8px;
            font-size: 0.9em;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 30px;
        }
        
        /* Modal para ver imagen completa */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }
        
        .modal-close:hover {
            color: #ccc;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5em;
            }
            
            .gallery {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>🖼️ Galería ESP32-CAM</h1>
                <div class="header-info">
                    <div class="photo-count">
                        <?php echo $total ?? 0; ?> fotos
                    </div>
                    <button class="btn-primary" onclick="goToLive()">
                        📹 Ver en Vivo
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (empty($images)): ?>
            <div class="empty-state">
                <h2>📷 No hay fotos aún</h2>
                <p>Las fotos capturadas por tu ESP32-CAM aparecerán aquí</p>
                <button class="btn-primary" onclick="goToLive()">
                    Ir a Transmisión en Vivo
                </button>
            </div>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($images as $image): ?>
                    <div class="photo-card">
                        <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                             alt="Foto capturada"
                             onclick="openModal('<?php echo htmlspecialchars($image['url']); ?>')">
                        <div class="photo-info">
                            <div class="timestamp">
                                📅 <?php echo htmlspecialchars($image['timestamp']); ?>
                            </div>
                            <div class="details">
                                <span><?php echo htmlspecialchars($image['filename']); ?></span>
                                <span><?php echo htmlspecialchars($image['size']); ?></span>
                            </div>
                        </div>
                        <div class="photo-actions">
                            <button class="btn-download" onclick="downloadPhoto('<?php echo htmlspecialchars($image['url']); ?>', '<?php echo htmlspecialchars($image['filename']); ?>')">
                                ⬇️ Descargar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal para ver imagen completa -->
    <div class="modal" id="imageModal" onclick="closeModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImage" src="" alt="Vista completa">
    </div>
    
    <script>
        function goToLive() {
            window.location.href = '/public/index.php?route=stream/live';
        }
        
        function downloadPhoto(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        function openModal(imageUrl) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = imageUrl;
            modal.classList.add('active');
        }
        
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
