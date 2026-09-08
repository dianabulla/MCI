<?php
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/GestionSistemaAccess.php';
require_once APP . '/Models/AuditoriaEvento.php';

class AuditoriaController extends BaseController {
    public function index() {
        GestionSistemaAccess::denegarSiNoPuedeVerCuentas();

        $modelo = new AuditoriaEvento();
        $filtros = [
            'modulo' => trim((string)($_GET['modulo'] ?? '')),
            'accion' => trim((string)($_GET['accion'] ?? '')),
            'exito' => $_GET['exito'] ?? '',
        ];
        if ($filtros['exito'] === 'todos') {
            $filtros['exito'] = '';
        }

        $this->view('auditoria/lista', [
            'eventos' => $modelo->listarRecientes($filtros, 250),
            'filtros' => $filtros,
        ]);
    }
}
