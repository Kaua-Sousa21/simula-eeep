<?php
// public/logout.php
// ============================================================

require_once '../src/config/config.php';
require_once '../src/helpers/Session.php';
require_once '../src/helpers/Redirect.php';
require_once '../src/helpers/Flash.php';

Session::iniciar();
Session::destruir();

Flash::sucesso('Você saiu do sistema com segurança.');
Redirect::voltarLogin();
