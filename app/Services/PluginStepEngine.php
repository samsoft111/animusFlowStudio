<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Motor de passos dos PLUGINS — o "espelho" do processo de criação de plugin.
 * A lógica (classificar, registar, podar, reverter, publicJournal) vive em
 * AbstractStepEngine; aqui ficam só os dados específicos dos plugins.
 */
class PluginStepEngine extends AbstractStepEngine
{
    /** Mapa campo → passo (a "lógica do processo" dos plugins). */
    public const FIELD_STEP = [
        'label'                  => 'details',
        'description'            => 'details',
        'version'                => 'details',
        'author'                 => 'details',
        'author_url'             => 'details',
        'category'               => 'details',
        'tags'                   => 'details',
        'license'                => 'details',
        'min_animusflow_version' => 'details',
        'homepage_url'           => 'details',
        'status'                 => 'details',
        'hooks'                  => 'hooks',
        'plugin_php'             => 'php',
        'widget_blade'           => 'widget',
        'widget_js'              => 'widget',
        'custom_css'             => 'css',
        'settings_schema'        => 'schema',
        'readme'                 => 'docs',
    ];

    /** Rótulos legíveis por passo para plugins. */
    public const STEP_LABELS = [
        'details' => 'Detalhes',
        'hooks'   => 'Hooks',
        'php'     => 'PHP',
        'widget'  => 'Widget',
        'css'     => 'CSS',
        'schema'  => 'Configurações',
        'docs'    => 'Docs',
    ];

    /** Palavras-chave por passo (fallback determinístico para texto livre, PT-PT). */
    protected const KEYWORDS = [
        'details' => ['nome', 'título', 'titulo', 'descrição', 'descricao', 'versão', 'versao', 'autor', 'categoria', 'tag', 'licença', 'licenca', 'homepage', 'status'],
        'hooks'   => ['hook', 'hooks', 'evento', 'eventos', 'page.render', 'content.publish', 'admin.sidebar'],
        'php'     => ['php', 'classe', 'class', 'scaffold', 'método', 'metodo', 'função', 'funcao', 'plugin.php', 'plugin_php'],
        'widget'  => ['widget', 'blade', 'html', 'js', 'javascript', 'widget_blade', 'widget_js', 'widget.blade.php', 'widget.js'],
        'css'     => ['css', 'estilo', 'custom_css', 'plugin.css', 'classe css', 'design css', 'layout css'],
        'schema'  => ['config', 'configurac', 'configuraç', 'settings', 'schema', 'campo', 'campos', 'settings_schema'],
        'docs'    => ['readme', 'docs', 'documentação', 'documentacao', 'readme.md'],
    ];

    /** Classificador IA específico dos plugins (só quando ambíguo). */
    protected static function aiClassify(string $message): ?string
    {
        return AIEngine::classifyPluginStep($message, static::steps());
    }
}
