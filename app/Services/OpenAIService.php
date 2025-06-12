<?php

namespace App\Services;

class OpenAIService
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
        $this->model = getenv('OPENAI_MODEL') ?: 'gpt-3.5-turbo';
        
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in your environment.');
        }
    }

    /**
     * Generate AI-powered content for astrological reports
     * 
     * @param array $data User birth data and historical information
     * @return array Generated AI content
     */
    public function generateReportContent($data)
    {
        $birthDate = $data['birth_date'];
        $zodiacSign = $this->getZodiacSign($birthDate);
        $chineseZodiac = $this->getChineseZodiac($birthDate);
        $historicalData = $data['historical_data'] ?? [];
        
        $aiContent = [];
        
        // Generate Soul's Archetype
        $aiContent['souls_archetype'] = $this->generateSoulsArchetype($zodiacSign, $chineseZodiac, $birthDate);
        
        // Generate Planetary Influence
        $aiContent['planetary_influence'] = $this->generatePlanetaryInfluence($zodiacSign, $birthDate);
        
        // Generate Life Path Number interpretation
        $lifePathNumber = $this->calculateLifePathNumber($birthDate);
        $aiContent['life_path_number'] = [
            'number' => $lifePathNumber,
            'interpretation' => $this->generateLifePathInterpretation($lifePathNumber, $zodiacSign)
        ];
        
        // Generate personalized summary based on historical data
        if (!empty($historicalData)) {
            $aiContent['cosmic_summary'] = $this->generateCosmicSummary($zodiacSign, $chineseZodiac, $historicalData, $birthDate);
        }
        
        return $aiContent;
    }

    /**
     * Generate AI-powered insights for a given Archetype.
     *
     * @param \App\Models\Archetype \$archetype The archetype object.
     * @return array Generated AI content for the archetype.
     */
    public function generateArchetypeInsights(\App\Models\Archetype \$archetype)
    {
        \$archetypeName = \$archetype->name;
        \$archetypeDescription = \$archetype->description;

        \$prompt = "Given the archetype '{\$archetypeName}' described as '{\$archetypeDescription}', provide a concise and insightful analysis. Include:
        1. Core Essence: A 1-2 sentence summary of what this archetype fundamentally represents.
        2. Key Strengths: 2-3 primary strengths or positive attributes associated with this archetype.
        3. Potential Challenges: 1-2 common challenges or shadow aspects this archetype might face.
        4. Symbolic Representation: A brief (1 sentence) symbolic image or metaphor for this archetype.
        Make the tone empowering and philosophical. Format the output as a JSON object with keys: 'core_essence', 'key_strengths' (array of strings), 'potential_challenges' (array of strings), and 'symbolic_representation'.";

        \$rawResponse = \$this->makeOpenAIRequest(\$prompt, 300); // Increased maxTokens for potentially richer content

        // Attempt to parse the JSON response from AI
        \$parsedResponse = json_decode(\$rawResponse, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array(\$parsedResponse)) {
            // Validate that expected keys are present, otherwise return a default structure or the raw text
            return [
                'core_essence' => \$parsedResponse['core_essence'] ?? 'Insight generation in progress.',
                'key_strengths' => \$parsedResponse['key_strengths'] ?? [],
                'potential_challenges' => \$parsedResponse['potential_challenges'] ?? [],
                'symbolic_representation' => \$parsedResponse['symbolic_representation'] ?? '',
                'raw_text' => empty(\$parsedResponse) ? \$rawResponse : '' // Include raw if parsing failed or was incomplete
            ];
        } else {
            // If AI did not return valid JSON, return the raw text under a generic key or handle error
            // This allows for manual review or a simpler display if structured parsing fails.
            return [
                'core_essence' => 'Detailed insights for this archetype are being generated. Please check back soon.',
                'key_strengths' => [],
                'potential_challenges' => [],
                'symbolic_representation' => '',
                'raw_text' => \$rawResponse // Store the raw response for debugging or fallback display
            ];
        }
    }

    /**
     * Generate Soul's Archetype using AI
     */
    private function generateSoulsArchetype($zodiacSign, $chineseZodiac, $birthDate)
    {
        $prompt = "Based on someone born on {$birthDate} with zodiac sign {$zodiacSign} and Chinese zodiac {$chineseZodiac}, determine their Soul's Archetype. Choose from archetypes like 'The Guardian', 'The Explorer', 'The Visionary', 'The Healer', 'The Creator', 'The Warrior', 'The Sage', 'The Lover', 'The Rebel', 'The Magician'. Provide the archetype name and a 2-3 sentence empowering description of their core essence and purpose.";
        
        $response = $this->makeOpenAIRequest($prompt, 150);
        
        // Parse the response to extract archetype name and description
        $lines = explode("\n", trim($response));
        $archetypeName = trim($lines[0]);
        $description = trim(implode(" ", array_slice($lines, 1)));
        
        return [
            'name' => $archetypeName,
            'description' => $description
        ];
    }

    /**
     * Generate Planetary Influence interpretation
     */
    private function generatePlanetaryInfluence($zodiacSign, $birthDate)
    {
        $rulingPlanet = $this->getRulingPlanet($zodiacSign);
        
        $prompt = "For someone born on {$birthDate} with zodiac sign {$zodiacSign}, write a personalized 3-4 sentence interpretation of how their ruling planet {$rulingPlanet} influences their personality, strengths, and life approach. Make it empowering and insightful.";
        
        return $this->makeOpenAIRequest($prompt, 200);
    }

    /**
     * Generate Life Path Number interpretation
     */
    private function generateLifePathInterpretation($lifePathNumber, $zodiacSign)
    {
        $prompt = "Provide a detailed, empowering interpretation for Life Path Number {$lifePathNumber} for someone with zodiac sign {$zodiacSign}. Include their life purpose, natural talents, challenges to overcome, and guidance for fulfilling their potential. Write 4-5 sentences.";
        
        return $this->makeOpenAIRequest($prompt, 250);
    }

    /**
     * Generate cosmic summary based on historical data
     */
    private function generateCosmicSummary($zodiacSign, $chineseZodiac, $historicalData, $birthDate)
    {
        $events = $historicalData['events'] ?? [];
        $births = $historicalData['births'] ?? [];
        $deaths = $historicalData['deaths'] ?? [];
        
        $historicalContext = "";
        if (!empty($events)) {
            $historicalContext .= "Historical events on this day: " . implode(", ", array_slice($events, 0, 3)) . ". ";
        }
        if (!empty($births)) {
            $historicalContext .= "Notable people born on this day: " . implode(", ", array_slice($births, 0, 2)) . ". ";
        }
        
        $prompt = "Create an inspiring cosmic summary for someone born on {$birthDate} ({$zodiacSign}, {$chineseZodiac}). {$historicalContext} Connect their birth date's historical significance to their personal cosmic blueprint and potential. Write 3-4 empowering sentences about their unique place in the cosmic tapestry.";
        
        return $this->makeOpenAIRequest($prompt, 200);
    }

    /**
     * Make request to OpenAI API
     */
    private function makeOpenAIRequest($prompt, $maxTokens = 150)
    {
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a wise and empowering astrological guide. Provide personalized, positive, and insightful interpretations that help people understand their cosmic blueprint and potential.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ];
        
        $options = [
            'http' => [
                'header' => [
                    "Content-Type: application/json",
                    "Authorization: Bearer {$this->apiKey}"
                ],
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($this->baseUrl . '/chat/completions', false, $context);
        
        if ($response === false) {
            throw new \Exception('Failed to connect to OpenAI API');
        }
        
        $responseData = json_decode($response, true);
        
        if (isset($responseData['error'])) {
            throw new \Exception('OpenAI API Error: ' . $responseData['error']['message']);
        }
        
        // Check if choices exist and have content
        if (isset(\$responseData['choices'][0]['message']['content'])) {
            return trim(\$responseData['choices'][0]['message']['content']);
        } else {
            // Log the problematic response for debugging
            error_log('OpenAI API response did not contain expected content structure: ' . json_encode(\$responseData));
            // Throw an exception or return an error message/empty string
            throw new \Exception('OpenAI API Error: Invalid response structure. Choices or content missing.');
            // Alternatively, return a default or empty string if preferred over an exception:
            // return ''; 
        }
    }

    /**
     * Calculate Life Path Number from birth date
     */
    private function calculateLifePathNumber($birthDate)
    {
        $date = str_replace('-', '', $birthDate);
        $sum = array_sum(str_split($date));
        
        // Reduce to single digit (except master numbers 11, 22, 33)
        while ($sum > 9 && !in_array($sum, [11, 22, 33])) {
            $sum = array_sum(str_split($sum));
        }
        
        return $sum;
    }

    /**
     * Get zodiac sign from birth date
     */
    private function getZodiacSign($birthDate)
    {
        $date = new \DateTime($birthDate);
        $month = (int)$date->format('n');
        $day = (int)$date->format('j');
        
        $signs = [
            'Capricorn' => [12, 22], 'Aquarius' => [1, 20], 'Pisces' => [2, 19],
            'Aries' => [3, 21], 'Taurus' => [4, 20], 'Gemini' => [5, 21],
            'Cancer' => [6, 21], 'Leo' => [7, 23], 'Virgo' => [8, 23],
            'Libra' => [9, 23], 'Scorpio' => [10, 23], 'Sagittarius' => [11, 22]
        ];
        
        foreach ($signs as $sign => $dates) {
            if (($month == $dates[0] && $day >= $dates[1]) || 
                ($month == ($dates[0] % 12) + 1 && $day < $dates[1])) {
                return $sign;
            }
        }
        
        return 'Capricorn'; // Default fallback
    }

    /**
     * Get Chinese zodiac sign from birth date
     */
    private function getChineseZodiac($birthDate)
    {
        $year = (int)date('Y', strtotime($birthDate));
        $animals = ['Monkey', 'Rooster', 'Dog', 'Pig', 'Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake', 'Horse', 'Goat'];
        return $animals[$year % 12];
    }

    /**
     * Get ruling planet for zodiac sign
     */
    private function getRulingPlanet($zodiacSign)
    {
        $planets = [
            'Aries' => 'Mars', 'Taurus' => 'Venus', 'Gemini' => 'Mercury',
            'Cancer' => 'Moon', 'Leo' => 'Sun', 'Virgo' => 'Mercury',
            'Libra' => 'Venus', 'Scorpio' => 'Pluto', 'Sagittarius' => 'Jupiter',
            'Capricorn' => 'Saturn', 'Aquarius' => 'Uranus', 'Pisces' => 'Neptune'
        ];
        
        return $planets[$zodiacSign] ?? 'Unknown';
    }

    /**
     * Generate daily vibe content
     */
    public function generateDailyVibe($zodiacSign, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $prompt = "Generate a one-sentence inspiring daily cosmic vibe for {$zodiacSign} on {$date}. Make it uplifting, actionable, and mystical.";
        
        return $this->makeOpenAIRequest($prompt, 50);
    }

    /**
     * Generate compatibility report between two zodiac signs
     */
    public function generateCompatibilityReport($sign1, $sign2, $birthDate1, $birthDate2)
    {
        $prompt = "Create a fun, insightful compatibility report between {$sign1} (born {$birthDate1}) and {$sign2} (born {$birthDate2}). Include their strengths as a pair, potential challenges, and advice for harmony. Write 4-5 sentences that are both accurate and encouraging.";
        
        return $this->makeOpenAIRequest($prompt, 300);
    }
    
    /**
     * Generate premium compatibility insights
     */
    public function generateCompatibilityInsights($data)
    {
        $prompt = "Generate detailed compatibility insights for premium users. Include information about relationship dynamics, communication styles, emotional compatibility, and long-term potential. Provide actionable advice for building stronger connections. Write 3-4 paragraphs with deep, personalized insights.";
        
        return $this->makeOpenAIRequest($prompt, 400);
    }
    
    /**
     * Generate premium archetype insights
     */
    public function generateArchetypeInsights($data)
    {
        $archetypeName = $data['archetype_name'] ?? 'Unknown';
        $archetypeDescription = $data['archetype_description'] ?? '';
        
        $prompt = "Generate deep, premium insights for the {$archetypeName} archetype. Based on this description: {$archetypeDescription}. Include detailed analysis of personality traits, life purpose, shadow aspects, growth opportunities, career paths, relationship patterns, and spiritual development. Provide actionable guidance for personal transformation. Write 4-5 comprehensive paragraphs with profound insights.";
        
        return $this->makeOpenAIRequest($prompt, 500);
    }
}