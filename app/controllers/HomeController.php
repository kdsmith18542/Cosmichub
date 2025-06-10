<?php
namespace App\Controllers;

use App\Libraries\Controller;

class HomeController extends Controller
{
    /**
     * Display the home page
     */
    public function index()
    {
        $data = [
            'title' => 'Welcome to CosmicHub',
            'description' => 'Your personal astrological report generator'
        ];
        
        $this->view('home/index', $data);
    }
}
