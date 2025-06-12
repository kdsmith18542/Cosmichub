<?php

namespace App\Helpers;

class AstrologyHelper
{
    /**
     * Get Western Zodiac Sign based on month and day.
     *
     * @param int $month
     * @param int $day
     * @return string
     */
    public static function getWesternZodiacSign(int $month, int $day): string
    {
        if (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
            return "Aquarius";
        }
        if (($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
            return "Pisces";
        }
        if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
            return "Aries";
        }
        if (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
            return "Taurus";
        }
        if (($month == 5 && $day >= 21) || ($month == 6 && $day <= 20)) {
            return "Gemini";
        }
        if (($month == 6 && $day >= 21) || ($month == 7 && $day <= 22)) {
            return "Cancer";
        }
        if (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
            return "Leo";
        }
        if (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
            return "Virgo";
        }
        if (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) {
            return "Libra";
        }
        if (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) {
            return "Scorpio";
        }
        if (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) {
            return "Sagittarius";
        }
        if (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
            return "Capricorn";
        }
        return "Unknown";
    }

    /**
     * Get Chinese Zodiac Sign based on year.
     *
     * @param int $year
     * @return string
     */
    public static function getChineseZodiacSign(int $year): string
    {
        $animals = [
            'Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake',
            'Horse', 'Goat', 'Monkey', 'Rooster', 'Dog', 'Pig'
        ];
        // Chinese zodiac starts from 1900 (Rat) for this calculation method
        // (Year - 1900) % 12 gives the index in the animals array
        // Ensure year is not before a common reference point if needed for older dates
        if ($year < 1900) {
            // For simplicity, returning Unknown for years before 1900 or handle with a more complex algorithm
            // This basic calculation might be off for early January/February births due to Lunar New Year timing.
            // A more accurate version would require Lunar New Year dates.
            return "Unknown (Requires Lunar Calendar for pre-1900 or early year births)";
        }
        return $animals[($year - 1900) % 12];
    }

    /**
     * Get Birthstone based on month.
     *
     * @param int $month
     * @return string
     */
    public static function getBirthstone(int $month): string
    {
        $stones = [
            1 => 'Garnet',
            2 => 'Amethyst',
            3 => 'Aquamarine',
            4 => 'Diamond',
            5 => 'Emerald',
            6 => 'Pearl / Alexandrite',
            7 => 'Ruby',
            8 => 'Peridot / Spinel',
            9 => 'Sapphire',
            10 => 'Opal / Tourmaline',
            11 => 'Topaz / Citrine',
            12 => 'Turquoise / Tanzanite / Zircon'
        ];
        return $stones[$month] ?? 'Unknown';
    }

    /**
     * Get Birth Flower based on month.
     *
     * @param int $month
     * @return string
     */
    public static function getBirthFlower(int $month): string
    {
        $flowers = [
            1 => 'Carnation / Snowdrop',
            2 => 'Violet / Primrose',
            3 => 'Daffodil / Jonquil',
            4 => 'Daisy / Sweet Pea',
            5 => 'Lily of the Valley / Hawthorn',
            6 => 'Rose / Honeysuckle',
            7 => 'Larkspur / Water Lily',
            8 => 'Gladiolus / Poppy',
            9 => 'Aster / Morning Glory',
            10 => 'Marigold / Cosmos',
            11 => 'Chrysanthemum',
            12 => 'Narcissus / Holly / Poinsettia'
        ];
        return $flowers[$month] ?? 'Unknown';
    }

    /**
     * Calculate Life Path Number from a birth date (Y-m-d string or DateTime object).
     *
     * @param string|\DateTimeInterface $birthDate
     * @return int
     */
    public static function calculateLifePathNumber($birthDate): int
    {
        if (is_string($birthDate)) {
            $date = new \DateTime($birthDate);
        } elseif ($birthDate instanceof \DateTimeInterface) {
            $date = $birthDate;
        } else {
            throw new \InvalidArgumentException('Birth date must be a Y-m-d string or DateTime object.');
        }

        $year = (string)$date->format('Y');
        $month = (string)$date->format('m');
        $day = (string)$date->format('d');

        $sumYear = self::reduceToSingleDigitOrMaster((int)array_sum(str_split($year)));
        $sumMonth = self::reduceToSingleDigitOrMaster((int)array_sum(str_split($month)));
        $sumDay = self::reduceToSingleDigitOrMaster((int)array_sum(str_split($day)));

        $totalSum = $sumYear + $sumMonth + $sumDay;
        
        return self::reduceToSingleDigitOrMaster($totalSum);
    }

    /**
     * Reduces a number by summing its digits until it's a single digit or a master number (11, 22, 33).
     *
     * @param int $number
     * @return int
     */
    private static function reduceToSingleDigitOrMaster(int $number): int
    {
        // Master numbers are not reduced further
        if (in_array($number, [11, 22, 33])) {
            return $number;
        }
        while ($number > 9) {
            $number = array_sum(str_split((string)$number));
            // Check for master numbers after a reduction step
            if (in_array($number, [11, 22, 33])) {
                return $number;
            }
        }
        return $number;
    }
    
    /**
     * Get a brief interpretation of a Life Path Number.
     * (These are very generic, for a real app, more detailed or AI-generated text would be better)
     *
     * @param int $lifePathNumber
     * @return string
     */
    public static function getLifePathNumberInterpretation(int $lifePathNumber): string
    {
        $interpretations = [
            1 => 'Leader, independent, pioneer, innovative, determined.',
            2 => 'Cooperative, diplomat, sensitive, peacemaker, intuitive.',
            3 => 'Expressive, creative, social, optimistic, artistic.',
            4 => 'Practical, organized, hardworking, disciplined, stable.',
            5 => 'Adventurous, versatile, freedom-loving, dynamic, curious.',
            6 => 'Responsible, nurturing, harmonious, protective, community-oriented.',
            7 => 'Analytical, introspective, spiritual, seeker of truth, wise.',
            8 => 'Ambitious, authoritative, powerful, successful, materialistic.',
            9 => 'Humanitarian, compassionate, idealistic, selfless, wise.',
            11 => 'Intuitive, visionary, idealistic, inspirational, spiritual teacher (Master Number).',
            22 => 'Master builder, practical idealist, powerful, disciplined, capable of great achievements (Master Number).',
            33 => 'Master teacher, compassionate, nurturing, spiritual guide, highly evolved (Master Number).'
        ];
        return $interpretations[$lifePathNumber] ?? 'No interpretation available.';
    }
}