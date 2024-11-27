<?php
declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\HttpFoundation\Request;


final class ProblematicOverrideExtractor
{
    public static function extractFromRequest(Request $request): bool
    {
        if($request->query->has('problematicOverride') === false)
            return false;

        return $request->query->get('problematicOverride') === 'true';
    }
}