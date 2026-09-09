<?php

declare(strict_types=1);

/**
 * Home Controller - Default controller for home page
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Controllers;

class HomeController extends Controller
{
    /**
     * Display home page
     *
     * @return void
     */
    public function index(): void
    {
        $this->with('title', 'Welcome')
             ->with('message', 'Welcome to our application');
        
        // For API response
        if ($this->isApiRequest()) {
            $this->json([
                'success' => true,
                'data' => [
                    'title' => 'Welcome',
                    'message' => 'Welcome to our application',
                    'version' => '1.0.0'
                ]
            ]);
        }
        
        // For web view
        $this->view('home/index');
    }

    /**
     * Display about page
     *
     * @return void
     */
    public function about(): void
    {
        $this->with('title', 'About Us')
             ->with('content', 'This is a production-grade PHP framework built with SOLID principles.');
        
        if ($this->isApiRequest()) {
            $this->json([
                'success' => true,
                'data' => [
                    'title' => 'About Us',
                    'content' => 'This is a production-grade PHP framework built with SOLID principles.'
                ]
            ]);
        }
        
        $this->view('home/about');
    }

    /**
     * Check if request is API request
     *
     * @return bool
     */
    protected function isApiRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) 
            && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    }
}
