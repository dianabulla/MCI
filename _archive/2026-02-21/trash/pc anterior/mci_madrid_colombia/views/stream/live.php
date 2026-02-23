<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Transmisión en Vivo'; ?></title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ff4444;
            animation: pulse 2s infinite;
        }
        
        .status-dot.active {
            background: #00ff00;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }
        }
        
        .video-container {
            position: relative;
            background: #000;
            aspect-ratio: 4/3;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #streamImage {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .no-signal {
            color: white;
            font-size: 1.5em;
            text-align: center;
            padding: 40px;
        }
        
        .controls {
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-item label {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 4px;
        }
        
        .info-item span {
            font-weight: bold;
            color: #333;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
        }
        
        button {
            padding: 10px 20px;
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        }
        
        .snapshot-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .snapshot-notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5em;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .buttons {
                justify-content: stretch;
            }
            
            button {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📹 Transmisión en Vivo ESP32-CAM</h1>
            <div class="status">
                <div class="status-dot" id="statusDot"></div>
                <span id="statusText">Esperando señal...</span>
            </div>
        </div>
        
        <div class="video-container">
            <img id="streamImage" style="display: none;" alt="Stream">
            <div class="no-signal" id="noSignal">
                <div>⚠️ Sin señal</div>
                <div style="font-size: 0.6em; margin-top: 10px;">Esperando transmisión desde ESP32-CAM</div>
            </div>
        </div>
        
        <div class="controls">
            <div class="info">
                <div class="info-item">
                    <label>Última actualización</label>
                    <span id="lastUpdate">--:--:--</span>
                </div>
                <div class="info-item">
                    <label>FPS estimado</label>
                    <span id="fps">0</span>
                </div>
                <div class="info-item">
                    <label>Total de fotos</label>
                    <span id="totalPhotos">0</span>
                </div>
            </div>
            
            <div class="buttons">
                <button class="btn-secondary" onclick="takeSnapshot()">📸 Capturar</button>
                <button class="btn-primary" onclick="goToGallery()">🖼️ Galería</button>
            </div>
        </div>
    </div>
    
    <div class="snapshot-notification" id="notification">
        ✓ Foto guardada
    </div>
    
    <script>
        let lastUpdateTime = 0;
        let frameCount = 0;
        let fpsHistory = [];
        let updateInterval;
        let hasSignal = false;
        
        // Actualizar imagen cada segundo
        function updateImage() {
            const img = document.getElementById('streamImage');
            const noSignal = document.getElementById('noSignal');
            const statusDot = document.getElementById('statusDot');
            const statusText = document.getElementById('statusText');
            
            // Agregar timestamp para evitar caché
            const timestamp = new Date().getTime();
            const newSrc = '/public/assets/stream/latest.jpg?' + timestamp;
            
            // Crear una imagen temporal para verificar si existe
            const tempImg = new Image();
            
            tempImg.onload = function() {
                img.src = newSrc;
                img.style.display = 'block';
                noSignal.style.display = 'none';
                statusDot.classList.add('active');
                statusText.textContent = 'En vivo';
                hasSignal = true;
                
                // Actualizar estadísticas
                updateStats();
            };
            
            tempImg.onerror = function() {
                if (hasSignal) {
                    statusDot.classList.remove('active');
                    statusText.textContent = 'Señal perdida';
                }
            };
            
            tempImg.src = newSrc;
        }
        
        function updateStats() {
            const now = Date.now();
            
            // Calcular FPS
            if (lastUpdateTime > 0) {
                const timeDiff = (now - lastUpdateTime) / 1000;
                const currentFps = 1 / timeDiff;
                fpsHistory.push(currentFps);
                
                // Mantener solo los últimos 10 valores
                if (fpsHistory.length > 10) {
                    fpsHistory.shift();
                }
                
                // Calcular promedio
                const avgFps = fpsHistory.reduce((a, b) => a + b, 0) / fpsHistory.length;
                document.getElementById('fps').textContent = avgFps.toFixed(1);
            }
            
            lastUpdateTime = now;
            frameCount++;
            
            // Actualizar hora
            const now_date = new Date();
            document.getElementById('lastUpdate').textContent = now_date.toLocaleTimeString('es-ES');
            
            // Obtener total de fotos
            fetch('/api/stream.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalPhotos').textContent = data.count;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function takeSnapshot() {
            const img = document.getElementById('streamImage');
            if (img.src && hasSignal) {
                const link = document.createElement('a');
                link.href = img.src;
                link.download = 'snapshot_' + new Date().getTime() + '.jpg';
                link.click();
                
                // Mostrar notificación
                const notification = document.getElementById('notification');
                notification.classList.add('show');
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 2000);
            }
        }
        
        function goToGallery() {
            window.location.href = '/public/index.php?route=stream/gallery';
        }
        
        // Iniciar actualización automática
        updateImage();
        updateInterval = setInterval(updateImage, 1000);
        
        // Limpiar intervalo al salir
        window.addEventListener('beforeunload', () => {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>
