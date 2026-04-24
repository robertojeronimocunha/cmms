<?php
/**
 * Ponto de entrada quando o Document Root do servidor aponta para a raiz do repositório
 * (/var/www/html) em vez de frontend/public. Evita página em branco ou 404 em "/".
 */
declare(strict_types=1);

require __DIR__ . '/frontend/public/index.php';
