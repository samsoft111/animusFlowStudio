<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Motor de passos dos TEMAS — o "espelho" do processo de criação de tema.
 * A lógica (classificar, registar, podar, reverter, publicJournal) vive em
 * AbstractStepEngine; aqui ficam só os dados específicos dos temas.
 *
 * ÂMBITO: cada editor tem o seu motor (temas e plugins). O Site Builder do CMS
 * tem um equivalente no repositório animusFlow. A lógica partilhada está na base
 * para evitar divergência entre eles.
 */
class ThemeStepEngine extends AbstractStepEngine
{
    /** Mapa campo → passo (a "lógica do processo" dos temas). */
    public const FIELD_STEP = [
        'label'         => 'details',
        'description'   => 'details',
        'version'       => 'details',
        'status'        => 'details',
        'colors'        => 'design',
        'fonts'         => 'design',
        'variants'      => 'variants',
        'layout_config' => 'layout',
        'capabilities'  => 'capabilities',
        'assets'        => 'assets',
        'sections'      => 'sections',
        'components'    => 'components',
        'custom_css'    => 'code',
        'custom_js'     => 'code',
    ];

    /** Rótulos legíveis por passo. */
    public const STEP_LABELS = [
        'details'      => 'Detalhes',
        'design'       => 'Design',
        'variants'     => 'Variantes',
        'layout'       => 'Layout',
        'capabilities' => 'Capacidades',
        'assets'       => 'Assets',
        'sections'     => 'Secções',
        'components'   => 'Componentes',
        'code'         => 'Código',
    ];

    /** Palavras-chave por passo (fallback determinístico para texto livre, PT-PT). */
    protected const KEYWORDS = [
        'design'       => ['cor', 'cores', 'paleta', 'fonte', 'tipografia', 'primária', 'primaria', 'secundária', 'accent', 'realce', 'branding', 'dark mode', 'modo escuro'],
        'layout'       => ['layout', 'header', 'cabeçalho', 'cabecalho', 'rodapé', 'rodape', 'footer', 'navegação', 'navegacao', 'menu', 'navbar', 'largura', 'max_width', 'sticky', 'espaçamento', 'espacamento', 'spacing'],
        'capabilities' => ['parallax', 'animaç', 'animac', 'lightbox', 'cookie', 'preloader', 'scroll', 'mega menu', 'pesquisa', 'capacidade', 'funcionalidade especial'],
        'sections'     => ['secção', 'seccao', 'secções', 'seccoes', 'hero', 'features', 'funcionalidades', 'preços', 'precos', 'pricing', 'testemunhos', 'galeria', 'faq', 'contacto', 'contato', 'cta', 'apelo'],
        'code'         => ['css', 'js', 'javascript', 'código', 'codigo', 'custom', 'estilo personalizado', 'script'],
        'variants'     => ['variante', 'variantes', 'paleta alternativa', 'skin', 'esquema de cor'],
        'assets'       => ['logo', 'logótipo', 'logotipo', 'favicon', 'imagem de fundo', 'og image', 'imagem og'],
        'details'      => ['nome do tema', 'título do tema', 'titulo do tema', 'descrição', 'descricao', 'versão', 'versao', 'renomear'],
    ];

    /** Classificador IA específico dos temas (só quando ambíguo). */
    protected static function aiClassify(string $message): ?string
    {
        return AIEngine::classifyThemeStep($message, static::steps());
    }
}
