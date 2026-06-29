# Skill genérico — Criação de Plugin (AnimusFlowStudio)

> 🏷️ **Sistema B — Geração.** Fluxo: criar tema/plugin no Studio → **instalar no AnimusFlow** → criar site.

Instruções para o **Chat IA do editor de plugin** gerar um plugin completo e instalável no AnimusFlow.

> Guia técnico completo (Sistema A): `animusFlow/skills/animusflow/references/plugin-development.md`.

---

## 1. Campos do plugin (aplicados pelo Chat IA)

| Campo | Conteúdo |
|-------|----------|
| `plugin_php` | A classe principal `Plugin.php` — implementa os métodos dos hooks ativos |
| `widget_blade` | `widget.blade.php` — markup do widget injetado no site |
| `widget_js` | `widget.js` — comportamento client-side |
| `custom_css` | `plugin.css` — estilos do widget |
| `settings_schema` | Campos configuráveis (form dinâmico em Admin → Plugins → Settings) |
| `hooks` | Hooks ativos (ver §3) |
| `label`, `description`, `version`, `status` | Meta |

## 2. Manifest — `animusflow-plugin.json`
Nome canónico do manifesto. Declara `name`, `label`, `version`, `hooks[]` e `settings[]`.

## 3. Hooks (mapa hook → método)

| Hook | Método | Efeito |
|------|--------|--------|
| `page.render` | `onPageRender(Page $page): string` | HTML injetado antes de `</body>` |
| `content.publish` | `onContentPublish(Page $page): void` | Dispara ao publicar uma página |
| `admin.sidebar` | `onAdminSidebar(): array` | `['label','icon','url']` no menu admin |

## 4. settings_schema (campos configuráveis)
Cada campo: `{ key, label, type, default, placeholder, hint }`.
Tipos: `text · textarea · color · select · toggle`. Para `select`, dar `"options": {"valor": "Label"}`.

*   **Persistência de Dados**: As configurações do plugin são gravadas automaticamente na tabela global de settings sob o namespace do plugin. No seu código PHP, recupere-as sempre utilizando:
    ```php
    $valor = \App\Models\StudioSetting::get('chave_config', 'default_value', 'plugins');
    ```
    Ou via modelo de Core:
    ```php
    $valor = \App\Models\Setting::get('chave_config', 'default_value', 'plugins');
    ```

## 5. Boas Práticas de Desenvolvimento de Plugins

*   **Degradação Graciosa (Graceful Fallback)**: Se o plugin exigir chaves de API, credenciais ou configurações que o utilizador ainda não preencheu, o widget **nunca deve quebrar a página**. Exiba uma mensagem amigável no lugar do widget para utilizadores autenticados (ex: "Configure a chave de API nas definições do plugin") ou oculte o widget silenciosamente para visitantes.
*   **Isolamento de JavaScript**: O script `widget.js` deve ser auto-contido e isolado (ex: dentro de um IIFE ou bloco `{}`) para evitar poluição do escopo global. Use seletores altamente específicos (baseados no ID do widget ou classes exclusivas) para que o script do plugin não interfira com o comportamento do tema ou de outros plugins.
*   **Integração com Modelos Core**: Para armazenar dados de utilizadores ou submissões, utilize as tabelas e modelos nativos do AnimusFlow sempre que possível (ex: `FormSubmission` para formulários) ou crie migrações limpas no seu plugin se for necessário estender o esquema.

## 6. Modo Construção (agentes)
O Chat IA decide quando faz um build completo e corre os agentes em fundo:

| Agente | Faz |
|--------|-----|
| `logic` 🧩 | `plugin_php` + hooks |
| `widget` 🎨 | `widget_blade` + `widget_js` + `custom_css` |
| `settings` ⚙️ | `settings_schema` |

Boas práticas: degradar com segurança se faltar configuração; nunca rebentar o render da página;
manter o widget acessível e responsivo.

## 6. Fluxo
Gerar → validar → **Install no CMS** (ou **Publish** no Marketplace) → ativar no AnimusFlow.
