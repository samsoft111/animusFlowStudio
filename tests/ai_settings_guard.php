<?php

/**
 * Guard das settings de IA para os scripts de teste do Studio.
 *
 * ⚠️ Estes scripts (`php tests/x.php`) correm contra a BD REAL `animusflow_studio`
 * (não há BD de teste isolada). Vários testes alteram/limpam `ai_api_key` para
 * exercitar o caminho "sem chave", o que apagava a chave real do utilizador.
 *
 * Este guard captura as settings de IA no arranque e restaura-as no fim — mesmo
 * após `exit()` — via register_shutdown_function. Inclui-o logo após o bootstrap
 * do Laravel em cada teste que mexa em settings de IA.
 */

$__aiGuardKeys = [
    'ai_provider', 'ai_model',
    'ai_api_key', 'ai_api_key_claude', 'ai_api_key_openai', 'ai_api_key_gemini',
    // Chaves de integração que os testes de export/plugin põem a '' no fim —
    // sem isto, correr esses scripts apagava as chaves reais do utilizador.
    'cms_url', 'cms_api_key', 'animusflow_api_key',
];

$__aiGuardOrig = [];
foreach ($__aiGuardKeys as $__k) {
    $__aiGuardOrig[$__k] = \App\Models\StudioSetting::get($__k, null);
}

register_shutdown_function(function () use ($__aiGuardOrig) {
    foreach ($__aiGuardOrig as $__k => $__v) {
        if ($__v === null) {
            continue; // não existia originalmente — deixar como está
        }
        \App\Models\StudioSetting::set($__k, $__v);
    }
});
