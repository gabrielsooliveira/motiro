<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Módulo Principal)
|--------------------------------------------------------------------------
|
| As rotas da aplicação são organizadas explicitamente por domínio e rotina:
| - Site público e institucional
| - Autenticação comum, logout e troca de contexto
| - Portal do Participante
| - Portal da Organização
|
*/

$folders = ['public', 'participant', 'organization'];

Route::group([], function () use ($folders) {
    foreach ($folders as $folder) {
        foreach (glob(base_path("routes/web/{$folder}/*.php")) as $fileName) {
            require $fileName;
        }
    }
});