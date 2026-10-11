<?php
/**
 * Academia System - configuracao
 *
 * Copie este arquivo para "config.php" e ajuste os dados.
 * O instalador (instalar.php) gera esse arquivo automaticamente.
 */

return [
    // --- Banco de dados (cPanel: usuario e banco costumam ter prefixo da conta) ---
    'db' => [
        'host'    => 'localhost',
        'porta'   => 3306,
        'nome'    => 'conta_academia',
        'usuario' => 'conta_usuario',
        'senha'   => 'sua-senha-do-banco',
        'charset' => 'utf8mb4',
    ],

    // --- Aplicacao ---
    'app' => [
        'nome'      => 'Academia System',
        // URL base publica. Deixe vazio para detectar automaticamente.
        // Ex.: 'https://seudominio.com.br' ou 'https://seudominio.com.br/sistema'
        'url'       => '',
        'timezone'  => 'America/Sao_Paulo',
        // Ambiente: 'producao' esconde erros; 'dev' mostra.
        'ambiente'  => 'producao',
    ],

    // --- Envio de e-mail (usa a funcao mail() do cPanel) ---
    'email' => [
        'ativo'      => false,
        'remetente'  => 'nao-responda@seudominio.com.br',
        'nome_from'  => 'Academia System',
    ],
];
