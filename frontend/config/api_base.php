<?php
/**
 * Valor inicial do campo "API Base" no CMMS.
 *
 * Ordem: CMMS_API_BASE (env) → protocolo + host da requisição atual → fallback local.
 * Em seguida local.php pode sobrescrever (desenvolvimento).
 *
 * Se o site só responder em HTTP (sem TLS na 443), acesse por http:// para o padrão
 * ser http://…/api/v1. Com HTTPS ativo, use https:// no navegador.
 */
declare(strict_types=1);

function cmms_detect_api_base_default(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return 'http://127.0.0.1:8000/api/v1';
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';

    return $scheme . '://' . $host . '/api/v1';
}

$cmmsApiBaseDefault = getenv('CMMS_API_BASE') ?: cmms_detect_api_base_default();

if (is_file(__DIR__ . '/local.php')) {
    require __DIR__ . '/local.php';
}
