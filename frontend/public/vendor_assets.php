<?php

declare(strict_types=1);

/**
 * Frontend offline-first: ficheiros em /assets/vendor/ (popular com scripts/update-frontend-vendor.sh).
 * Se o ficheiro local não existir, usa o URL do CDN (desenvolvimento ou até correr o script).
 *
 * Quando o DocumentRoot do servidor é a raiz do repositório (/var/www/html) e não frontend/public,
 * os URLs devem incluir o segmento /frontend/public para os estáticos serem encontrados.
 */
function cmms_public_web_prefix(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $doc = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
    $docReal = $doc !== '' ? realpath($doc) : false;
    $pubReal = realpath(__DIR__);
    if ($docReal === false || $pubReal === false) {
        $cached = '';

        return $cached;
    }
    if ($docReal === $pubReal) {
        $cached = '';

        return $cached;
    }
    $sep = DIRECTORY_SEPARATOR;
    if (!str_starts_with($pubReal, $docReal . $sep)) {
        $cached = '';

        return $cached;
    }
    $rel = substr($pubReal, strlen($docReal));
    $cached = str_replace($sep, '/', $rel);

    return $cached;
}

/** Caminho URL (com / inicial) para um ficheiro sob frontend/public, ex.: assets/branding/logo.png */
function cmms_public_uri(string $pathUnderPublic): string
{
    $path = ltrim(str_replace('\\', '/', $pathUnderPublic), '/');
    $prefix = rtrim(cmms_public_web_prefix(), '/');

    return ($prefix === '' ? '' : $prefix) . '/' . $path;
}

function cmms_vendor_uri(string $relativeUnderVendor, string $cdnFallback): string
{
    $rel = ltrim(str_replace('\\', '/', $relativeUnderVendor), '/');
    $full = __DIR__ . '/assets/vendor/' . $rel;
    if (is_file($full)) {
        return cmms_public_uri('assets/vendor/' . $rel);
    }

    return $cdnFallback;
}
