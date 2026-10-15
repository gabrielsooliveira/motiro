<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Módulo Principal)
|--------------------------------------------------------------------------
|
| As rotas da aplicação são organizadas explicitamente por domínio:
| - Site público e institucional
| - Autenticação comum, logout e troca de contexto
| - Portal do Participante (Auth e Dashboard)
| - Portal da Organização (Auth, Dashboard e Gestão)
|
*/

// Site público e páginas informativas
require __DIR__ . '/web/site.php';

// Autenticação comum, logout e troca de contexto
require __DIR__ . '/web/auth.php';

// Portal do Participante
require __DIR__ . '/web/participant/auth.php';
require __DIR__ . '/web/participant/dashboard.php';

// Portal da Organização
require __DIR__ . '/web/organization/auth.php';
require __DIR__ . '/web/organization/dashboard.php';
require __DIR__ . '/web/organization/organization.php';