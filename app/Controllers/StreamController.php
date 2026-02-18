<?php

class StreamController extends BaseController {
    
    /**
     * Mostrar vista de transmisión en vivo
     */
    public function live() {
        $this->view('stream/live', [
            'title' => 'Transmisión en Vivo - ESP32-CAM'
        ]);
    }
    
    /**
     * Mostrar galería de fotos capturadas
     */
    public function gallery() {
        $streamDir = __DIR__ . '/../../public/assets/stream/';
        $images = glob($streamDir . 'stream_*.jpg');
        
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
                'size' => $this->formatBytes(filesize($image))
            ];
        }
        
        $this->view('stream/gallery', [
            'title' => 'Galería de Fotos - ESP32-CAM',
            'images' => $imageList,
            'total' => count($imageList)
        ]);
    }
    
    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
