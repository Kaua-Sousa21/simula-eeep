<?php

// ============================================================
// Ponto de entrada principal do sistema Simula EEEP
// Responsabilidade: redirecionar o usuário para a página
// correta com base no seu estado de autenticação e tipo.
// ============================================================

require_once __DIR__ . '/../src/config/bootstrap.php';
require_once ROOT_PATH . '/src/helpers/Session.php';
require_once ROOT_PATH . '/src/helpers/Redirect.php';

Session::iniciar();

// ── Caso 1: Usuário NÃO está logado ──────────────────────────
if (!Session::autenticado()) {
    Redirect::para(BASE_URL . '/login.php');
}

// ── Caso 2: Primeiro acesso (senha temporária) ────────────────
if (Session::get('primeiro_acesso')) {
    Redirect::para(BASE_URL . '/primeiro-acesso.php');
}

// ── Caso 3: Administrador logado ─────────────────────────────
if (Session::isAdmin()) {
    Redirect::para(BASE_URL . '/admin/dashboard.php');
}

// ── Caso 4: Aluno logado ──────────────────────────────────────
Redirect::para(BASE_URL . '/aluno/dashboard.php');
