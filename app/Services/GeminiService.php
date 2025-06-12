<?php

namespace App\Services;

use Gemini;

class GeminiService
{
    private $client;
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        
        if (!$this->apiKey) {
            throw new \Exception('Gemini API key not found in environment variables');
        }
        
        // Initialize Gemini client
        $this->client = Gemini::client($this->apiKey);
    }

    /**
     * Generate Soul's Archetype content
     */
    public function generateSoulsArchetype($prompt)
    {
        try {
            $result = $this->client->generativeModel('gemini-1.5-flash')->generateContent($prompt);
            return $result->text();
        } catch (\Exception $e) {
            error_log('Gemini API Error (Soul\'s Archetype): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Planetary Influence content
     */
    public function generatePlanetaryInfluence($prompt)
    {
        try {
            $result = $this->client->generativeModel('gemini-1.5-flash')->generateContent($prompt);
            return $result->text();
        } catch (\Exception $e) {
            error_log('Gemini API Error (Planetary Influence): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Life Path Number interpretation
     */
    public function generateLifePathNumber($prompt)
    {
        try {
            $result = $this->client->generativeModel('gemini-1.5-flash')->generateContent($prompt);
            return $result->text();
        } catch (\Exception $e) {
            error_log('Gemini API Error (Life Path Number): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Cosmic Summary
     */
    public function generateCosmicSummary($prompt)
    {
        try {
            $result = $this->client->generativeModel('gemini-1.5-flash')->generateContent($prompt);
            return $result->text();
        } catch (\Exception $e) {
            error_log('Gemini API Error (Cosmic Summary): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Archetype Insights
     */
    public function generateArchetypeInsights($prompt)
    {
        try {
            $result = $this->client->generativeModel('gemini-1.5-flash')->generateContent($prompt);
            return $result->text();
        } catch (\Exception $e) {
            error_log('Gemini API Error (Archetype Insights): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured()
    {
        return !empty($this->apiKey);
    }
}