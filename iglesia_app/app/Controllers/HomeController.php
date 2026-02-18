<?php
/**
 * Controlador Home
 */

namespace App\Controllers;

class HomeController extends BaseController {
    /**
     * Índice / Dashboard
     */
    public function index() {
        $this->log('Acceso al dashboard');
        
        $this->assign('title', 'Dashboard');
        $this->render('home/dashboard');
    }
}
