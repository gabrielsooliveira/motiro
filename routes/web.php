<?php

// Carregar automaticamente todas as rotas dentro de routes/web/
foreach (glob(__DIR__ . '/web/*.php') as $routeFile) {
    require $routeFile;
}
