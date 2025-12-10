<?php

namespace App\Helpers;

class PrintHelper
{
    /**
     * Check if a modifier name is a portion/size type.
     *
     * @param string $modifierName
     * @return bool
     */
    public static function isPortionModifier(string $modifierName): bool
    {
        $modifierLower = strtolower($modifierName);
        
        // Common portion/size keywords
        $portionKeywords = [
            'full', 'half', 'quarter',
            'large', 'medium', 'small', 'regular',
            'mini', 'jumbo', 'king', 'queen',
            'single', 'double', 'triple',
            'ml', 'liter', 'litre', ' l', 'oz',
            'portion', 'size'
        ];
        
        foreach ($portionKeywords as $keyword) {
            if (strpos($modifierLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Filter out portion modifiers from a collection of modifiers.
     *
     * @param \Illuminate\Support\Collection $modifiers
     * @return \Illuminate\Support\Collection
     */
    public static function filterPortionModifiers($modifiers)
    {
        return $modifiers->filter(function ($modifier) {
            $modifierName = $modifier->modifier ? $modifier->modifier->name : ($modifier->modifier_name ?? '');
            return !self::isPortionModifier($modifierName);
        });
    }
}
