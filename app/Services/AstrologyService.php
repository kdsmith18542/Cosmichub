<?php

namespace App\Services;

use App\Core\Service\Service;
use App\Helpers\AstrologyHelper;

/**
 * Astrology Service for handling astrological calculations and data
 */
class AstrologyService extends Service
{
    /**
     * Get zodiac sign based on month and day
     *
     * @param int $month
     * @param int $day
     * @return string
     */
    public function getZodiacSign(int $month, int $day): string
    {
        return AstrologyHelper::getZodiacSign($month, $day);
    }

    /**
     * Get Western zodiac sign based on month and day
     *
     * @param int $month
     * @param int $day
     * @return string
     */
    public function getWesternZodiacSign(int $month, int $day): string
    {
        return AstrologyHelper::getWesternZodiacSign($month, $day);
    }

    /**
     * Get Chinese zodiac sign based on year
     *
     * @param int $year
     * @return string
     */
    public function getChineseZodiac(int $year): string
    {
        return AstrologyHelper::getChineseZodiac($year);
    }

    /**
     * Get Chinese zodiac sign based on year
     *
     * @param int $year
     * @return string
     */
    public function getChineseZodiacSign(int $year): string
    {
        return AstrologyHelper::getChineseZodiacSign($year);
    }

    /**
     * Calculate life path number
     *
     * @param int|string $month
     * @param int|string $day
     * @param int|string $year
     * @return int
     */
    public function calculateLifePathNumber($month, $day, $year): int
    {
        return AstrologyHelper::calculateLifePathNumber($month, $day, $year);
    }

    /**
     * Calculate rarity score
     *
     * @param int $month
     * @param int $day
     * @return float
     */
    public function calculateRarityScore(int $month, int $day): float
    {
        return AstrologyHelper::calculateRarityScore($month, $day);
    }

    /**
     * Get zodiac element
     *
     * @param string $zodiacSign
     * @return string
     */
    public function getZodiacElement(string $zodiacSign): string
    {
        return AstrologyHelper::getZodiacElement($zodiacSign);
    }

    /**
     * Get birthstone for a given month
     *
     * @param int $month
     * @return string
     */
    public function getBirthstone(int $month): string
    {
        return AstrologyHelper::getBirthstone($month);
    }

    /**
     * Get birth flower for a given month
     *
     * @param int $month
     * @return string
     */
    public function getBirthFlower(int $month): string
    {
        return AstrologyHelper::getBirthFlower($month);
    }

    /**
     * Get life path number interpretation
     *
     * @param int $lifePathNumber
     * @return string
     */
    public function getLifePathNumberInterpretation(int $lifePathNumber): string
    {
        return AstrologyHelper::getLifePathNumberInterpretation($lifePathNumber);
    }
}