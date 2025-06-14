<?php

namespace App\Controllers;

use App\Core\Controller\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\UserService;
use App\Services\ShareableService;
use App\Services\AstrologyService;
use Psr\Log\LoggerInterface;

class ShareableController extends Controller
{
    private UserService $userService;
    private ShareableService $shareableService;
    private AstrologyService $astrologyService;
    private LoggerInterface $logger;
    
    public function __construct()
    {
        parent::__construct();
        $this->userService = $this->resolve(UserService::class);
        $this->shareableService = $this->resolve(ShareableService::class);
        $this->astrologyService = $this->resolve(AstrologyService::class);
        $this->logger = $this->resolve(LoggerInterface::class);
    }
    /**
     * Generate animated shareable for cosmic snapshot
     */
    public function generateCosmicShareable(Request $request, $slug): Response
    {
        // Parse the birthday from slug
        $parts = explode('-', $slug);
        if (count($parts) !== 3) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        $month = (int)$parts[0];
        $day = (int)$parts[1];
        $year = (int)$parts[2];

        // Validate date
        if (!checkdate($month, $day, $year)) {
            return $this->json(['error' => 'Invalid date'], 400);
        }

        // Generate cosmic data
        $cosmicData = $this->generateCosmicData($month, $day, $year);
        
        // Return shareable data for frontend animation
        return $this->json([
            'success' => true,
            'data' => $cosmicData,
            'shareableUrl' => url("/cosmic-snapshot/{$slug}"),
            'animationConfig' => $this->getAnimationConfig($cosmicData)
        ]);
    }

    /**
     * Generate compatibility shareable
     */
    public function generateCompatibilityShareable(Request $request): Response
    {
        $this->requireLogin();
        
        $person1 = sanitize_input($request->input('person1', ''));
        $person2 = sanitize_input($request->input('person2', ''));
        $score = (int)$request->input('score', 0);
        $date1 = sanitize_input($request->input('date1', ''));
        $date2 = sanitize_input($request->input('date2', ''));

        if (empty($person1) || empty($person2) || $score < 0 || $score > 100) {
            return $this->json(['error' => 'Invalid compatibility data'], 400);
        }

        // Generate compatibility visual data
        $compatibilityData = [
            'person1' => $person1,
            'person2' => $person2,
            'score' => $score,
            'date1' => $date1,
            'date2' => $date2,
            'compatibility_level' => $this->getCompatibilityLevel($score),
            'cosmic_elements' => $this->getCosmicElements($date1, $date2)
        ];

        return $this->json([
            'success' => true,
            'data' => $compatibilityData,
            'animationConfig' => $this->getCompatibilityAnimationConfig($compatibilityData)
        ]);
    }

    /**
     * Generate rarity score shareable
     */
    public function generateRarityShareable(Request $request): Response
    {
        $this->requireLogin();
        
        $month = (int)$request->input('month', 0);
        $day = (int)$request->input('day', 0);
        $year = (int)$request->input('year', 0);
        $score = (int)$request->input('score', 0);

        if (!checkdate($month, $day, $year) || $score < 0 || $score > 100) {
            return $this->json(['error' => 'Invalid rarity data'], 400);
        }

        $rarityData = [
            'birth_date' => sprintf('%02d/%02d/%04d', $month, $day, $year),
            'rarity_score' => $score,
            'rarity_level' => $this->getRarityLevel($score),
            'cosmic_significance' => $this->getCosmicSignificance($month, $day),
            'zodiac_sign' => $this->astrologyService->getZodiacSign($month, $day)
        ];

        return $this->json([
            'success' => true,
            'data' => $rarityData,
            'animationConfig' => $this->getRarityAnimationConfig($rarityData)
        ]);
    }

    /**
     * Generate cosmic shareable (route method)
     */
    public function generateCosmic(Request $request): Response
    {
        $slug = $request->input('slug', '');
        if (empty($slug)) {
            return $this->json(['error' => 'Missing date slug'], 400);
        }
        return $this->generateCosmicShareable($request, $slug);
    }

    /**
     * Generate compatibility shareable (route method)
     */
    public function generateCompatibility(Request $request): Response
    {
        return $this->generateCompatibilityShareable($request);
    }

    /**
     * Generate rarity shareable (route method)
     */
    public function generateRarity(Request $request): Response
    {
        return $this->generateRarityShareable($request);
    }

    /**
     * View a generated shareable
     */
    public function view(Request $request, $id): Response
    {
        try {
            // Find the shareable by ID
            $shareable = $this->shareableService->findById($id);
            
            if (!$shareable) {
                // Redirect to home if shareable not found
                return $this->redirect('/');
            }
            
            // Decode the shareable data
            $data = json_decode($shareable->data, true);
            
            // Prepare view data
            $viewData = [
                'shareable' => $shareable,
                'data' => $data,
                'title' => 'Cosmic Blueprint - ' . ($data['birth_date'] ?? 'Shared Reading'),
                'meta_description' => 'Explore this cosmic blueprint featuring astrology, numerology, and mystical insights.',
                'og_image' => '/api/shareable/' . $id . '/preview.png'
            ];
            
            // Load the shareable view template
            return $this->view('shareable/view', $viewData);
            
        } catch (Exception $e) {
            $this->logger->error('Error viewing shareable: ' . $e->getMessage());
            return $this->redirect('/');
        }
    }

    /**
     * Download a generated shareable
     */
    public function download(Request $request, $id): Response
    {
        try {
            // Find the shareable by ID
            $shareable = $this->shareableService->findById($id);
            
            if (!$shareable) {
                return $this->json(['error' => 'Shareable not found'], 404);
            }
            
            // Check if user owns this shareable or if it's public
            if ($shareable->user_id && (!auth_check() || auth_user()->id !== $shareable->user_id)) {
                return $this->json(['error' => 'Unauthorized access'], 403);
            }
            
            // Generate the shareable content based on type
            $content = $this->generateShareableContent($shareable);
            
            if (!$content) {
                return $this->json(['error' => 'Failed to generate shareable content'], 500);
            }
            
            // Set appropriate headers for download
            $filename = $this->generateFilename($shareable);
            $mimeType = $this->getMimeType($shareable->format);
            
            return $this->response($content)
                ->withHeader('Content-Type', $mimeType)
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string)strlen($content))
                ->withHeader('Cache-Control', 'no-cache, must-revalidate');
            
        } catch (Exception $e) {
            $this->logger->error('Shareable download error: ' . $e->getMessage());
            return $this->json(['error' => 'Download failed'], 500);
        }
    }

    /**
     * Generate cosmic data for shareable
     */
    private function generateCosmicData($month, $day, $year)
    {
        $westernZodiac = $this->astrologyService->getZodiacSign($month, $day);
            $chineseZodiac = $this->astrologyService->getChineseZodiac($year);
            $lifePathNumber = $this->astrologyService->calculateLifePathNumber($month, $day, $year);
            $rarityScore = $this->astrologyService->calculateRarityScore($month, $day);

        return [
            'birth_date' => sprintf('%02d/%02d/%04d', $month, $day, $year),
            'western_zodiac' => $westernZodiac,
            'chinese_zodiac' => $chineseZodiac,
            'life_path_number' => $lifePathNumber,
            'rarity_score' => $rarityScore,
            'cosmic_colors' => $this->getCosmicColors($westernZodiac),
            'zodiac_symbol' => $this->getZodiacSymbol($westernZodiac)
        ];
    }

    /**
     * Get animation configuration for cosmic shareable
     */
    private function getAnimationConfig($cosmicData)
    {
        return [
            'type' => 'cosmic_snapshot',
            'duration' => 3000, // 3 seconds
            'background' => [
                'type' => 'gradient',
                'colors' => $cosmicData['cosmic_colors'],
                'animation' => 'pulse'
            ],
            'elements' => [
                [
                    'type' => 'zodiac_symbol',
                    'symbol' => $cosmicData['zodiac_symbol'],
                    'animation' => 'rotate_glow',
                    'duration' => 2000
                ],
                [
                    'type' => 'text',
                    'content' => $cosmicData['western_zodiac'],
                    'style' => 'title',
                    'animation' => 'fade_in',
                    'delay' => 500
                ],
                [
                    'type' => 'text',
                    'content' => "Life Path: {$cosmicData['life_path_number']}",
                    'style' => 'subtitle',
                    'animation' => 'slide_up',
                    'delay' => 1000
                ],
                [
                    'type' => 'text',
                    'content' => "Rarity: {$cosmicData['rarity_score']}%",
                    'style' => 'highlight',
                    'animation' => 'bounce',
                    'delay' => 1500
                ]
            ],
            'particles' => [
                'type' => 'stars',
                'count' => 50,
                'animation' => 'twinkle'
            ]
        ];
    }

    /**
     * Get compatibility animation configuration
     */
    private function getCompatibilityAnimationConfig($data)
    {
        return [
            'type' => 'compatibility',
            'duration' => 4000,
            'background' => [
                'type' => 'gradient',
                'colors' => $this->getCompatibilityColors($data['score']),
                'animation' => 'wave'
            ],
            'elements' => [
                [
                    'type' => 'heart',
                    'animation' => 'heartbeat',
                    'intensity' => $data['score'] / 100
                ],
                [
                    'type' => 'text',
                    'content' => "{$data['person1']} & {$data['person2']}",
                    'style' => 'names',
                    'animation' => 'fade_in'
                ],
                [
                    'type' => 'score_circle',
                    'value' => $data['score'],
                    'animation' => 'fill_circle',
                    'duration' => 2000
                ]
            ]
        ];
    }

    /**
     * Get rarity animation configuration
     */
    private function getRarityAnimationConfig($data)
    {
        return [
            'type' => 'rarity_score',
            'duration' => 3500,
            'background' => [
                'type' => 'cosmic_field',
                'rarity_level' => $data['rarity_level']
            ],
            'elements' => [
                [
                    'type' => 'rarity_gem',
                    'rarity' => $data['rarity_level'],
                    'animation' => 'sparkle_rotate'
                ],
                [
                    'type' => 'score_counter',
                    'target' => $data['rarity_score'],
                    'animation' => 'count_up',
                    'duration' => 2000
                ]
            ]
        ];
    }

    /**
     * Get cosmic colors for zodiac sign
     */
    private function getCosmicColors($zodiacSign)
    {
        $colorMap = [
            'Aries' => ['#FF6B6B', '#FF8E53'],
            'Taurus' => ['#4ECDC4', '#44A08D'],
            'Gemini' => ['#FFE66D', '#FF6B6B'],
            'Cancer' => ['#A8E6CF', '#7FCDCD'],
            'Leo' => ['#FFD93D', '#FF6B6B'],
            'Virgo' => ['#6BCF7F', '#4D9DE0'],
            'Libra' => ['#E15FED', '#B24BF3'],
            'Scorpio' => ['#8B0000', '#FF4500'],
            'Sagittarius' => ['#9B59B6', '#3498DB'],
            'Capricorn' => ['#2C3E50', '#34495E'],
            'Aquarius' => ['#3498DB', '#9B59B6'],
            'Pisces' => ['#1ABC9C', '#16A085']
        ];

        return $colorMap[$zodiacSign] ?? ['#667eea', '#764ba2'];
    }

    /**
     * Get zodiac symbol
     */
    private function getZodiacSymbol($zodiacSign)
    {
        $symbols = [
            'Aries' => '♈',
            'Taurus' => '♉',
            'Gemini' => '♊',
            'Cancer' => '♋',
            'Leo' => '♌',
            'Virgo' => '♍',
            'Libra' => '♎',
            'Scorpio' => '♏',
            'Sagittarius' => '♐',
            'Capricorn' => '♑',
            'Aquarius' => '♒',
            'Pisces' => '♓'
        ];

        return $symbols[$zodiacSign] ?? '✨';
    }

    /**
     * Get compatibility level
     */
    private function getCompatibilityLevel($score)
    {
        if ($score >= 90) return 'Cosmic Soulmates';
        if ($score >= 80) return 'Stellar Match';
        if ($score >= 70) return 'Strong Connection';
        if ($score >= 60) return 'Good Harmony';
        if ($score >= 50) return 'Moderate Compatibility';
        return 'Challenging Dynamic';
    }

    /**
     * Get compatibility colors based on score
     */
    private function getCompatibilityColors($score)
    {
        if ($score >= 80) return ['#FF6B9D', '#C44569'];
        if ($score >= 60) return ['#F8B500', '#FF6B6B'];
        if ($score >= 40) return ['#4ECDC4', '#44A08D'];
        return ['#95A5A6', '#7F8C8D'];
    }

    /**
     * Get cosmic elements for compatibility
     */
    private function getCosmicElements($date1, $date2)
    {
        // Parse dates and get zodiac signs
        $parts1 = explode('-', $date1);
        $parts2 = explode('-', $date2);
        
        if (count($parts1) === 3 && count($parts2) === 3) {
            $zodiac1 = $this->astrologyService->getZodiacSign((int)$parts1[1], (int)$parts1[2]);
            $zodiac2 = $this->astrologyService->getZodiacSign((int)$parts2[1], (int)$parts2[2]);
            
            return [
                'zodiac1' => $zodiac1,
                'zodiac2' => $zodiac2,
                'elements' => [
                    $this->astrologyService->getZodiacElement($zodiac1),
                $this->astrologyService->getZodiacElement($zodiac2)
                ]
            ];
        }
        
        return [];
    }

    /**
     * Get rarity level description
     */
    private function getRarityLevel($score)
    {
        if ($score >= 95) return 'Legendary';
        if ($score >= 85) return 'Epic';
        if ($score >= 70) return 'Rare';
        if ($score >= 50) return 'Uncommon';
        return 'Common';
    }

    /**
     * Get cosmic significance
     */
    private function getCosmicSignificance($month, $day)
    {
        $specialDates = [
            '01-01' => 'New Year Energy',
            '02-14' => 'Love Frequency',
            '03-21' => 'Spring Equinox Power',
            '06-21' => 'Summer Solstice Magic',
            '09-23' => 'Autumn Equinox Balance',
            '12-21' => 'Winter Solstice Wisdom',
            '10-31' => 'Mystical Veil Thin',
            '12-25' => 'Universal Love Day'
        ];

        $dateKey = sprintf('%02d-%02d', $month, $day);
        return $specialDates[$dateKey] ?? 'Unique Cosmic Signature';
    }

    /**
     * Generate shareable content based on format
     */
    private function generateShareableContent($shareable)
    {
        $data = json_decode($shareable->data, true);
        
        switch ($shareable->format) {
            case 'png':
            case 'jpg':
                return $this->generateImageContent($data, $shareable->format);
            case 'gif':
                return $this->generateAnimatedContent($data);
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
            case 'pdf':
                return $this->generatePdfContent($data);
            default:
                return null;
        }
    }
    
    /**
     * Generate filename for download
     */
    private function generateFilename($shareable)
    {
        $data = json_decode($shareable->data, true);
        $date = $data['birth_date'] ?? 'cosmic';
        $cleanDate = str_replace(['/', '-'], '', $date);
        
        return "cosmic-blueprint-{$cleanDate}.{$shareable->format}";
    }
    
    /**
     * Get MIME type for format
     */
    private function getMimeType($format)
    {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'json' => 'application/json',
            'pdf' => 'application/pdf'
        ];
        
        return $mimeTypes[$format] ?? 'application/octet-stream';
    }
    
    /**
     * Generate static image content
     */
    private function generateImageContent($data, $format)
    {
        // Create a simple image with cosmic data
        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        
        // Set colors
        $background = imagecolorallocate($image, 88, 28, 135); // Purple gradient start
        $textColor = imagecolorallocate($image, 255, 255, 255); // White text
        $accentColor = imagecolorallocate($image, 251, 191, 36); // Gold accent
        
        // Fill background
        imagefill($image, 0, 0, $background);
        
        // Add text content
        $fontSize = 20;
        $y = 100;
        
        imagestring($image, 5, 50, $y, 'Your Cosmic Blueprint', $textColor);
        $y += 60;
        
        if (isset($data['birth_date'])) {
            imagestring($image, 3, 50, $y, 'Birth Date: ' . $data['birth_date'], $textColor);
            $y += 40;
        }
        
        if (isset($data['western_zodiac'])) {
            imagestring($image, 3, 50, $y, 'Zodiac: ' . $data['western_zodiac'], $accentColor);
            $y += 40;
        }
        
        if (isset($data['life_path_number'])) {
            imagestring($image, 3, 50, $y, 'Life Path: ' . $data['life_path_number'], $textColor);
            $y += 40;
        }
        
        if (isset($data['rarity_score'])) {
            imagestring($image, 3, 50, $y, 'Rarity Score: ' . $data['rarity_score'] . '%', $accentColor);
        }
        
        // Output image
        ob_start();
        if ($format === 'png') {
            imagepng($image);
        } else {
            imagejpeg($image, null, 90);
        }
        $content = ob_get_clean();
        
        imagedestroy($image);
        return $content;
    }
    
    /**
     * Generate animated GIF content
     */
    private function generateAnimatedContent($data)
    {
        // For now, return a static image as GIF
        // In a full implementation, you would create multiple frames
        return $this->generateImageContent($data, 'gif');
    }
    
    /**
     * Generate PDF content
     */
    private function generatePdfContent($data)
    {
        // Simple text-based PDF content
        $content = "%PDF-1.4\n";
        $content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n";
        $content .= "4 0 obj\n<< /Length 100 >>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n(Your Cosmic Blueprint) Tj\nET\nendstream\nendobj\n";
        $content .= "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000206 00000 n \n";
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n356\n%%EOF";
        
        return $content;
    }
}