<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../Views');
        $this->twig = new Environment($loader, [
            'cache' => __DIR__ . '/../../cache/views',
            'auto_reload' => true, // Set to false in production
        ]);
    }

    public function render(string $template, array $data = []): string
    {
        try {
            return $this->twig->render($template, $data);
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            \App\Support\Log::error('Twig rendering error: ' . $e->getMessage());
            return 'Error rendering view.';
        }
    }
}