# Skills de geração de PLUGINS (Sistema B)

Prompts dados ao **Chat IA do editor de plugin** (AnimusFlowStudio → Plugins → editar → Chat IA / Modo
Construção, botão ✦) para gerar um plugin completo.

## Skills aqui

| Ficheiro | Tipo | Usar quando |
|----------|------|-------------|
| `plugin-creation-skill.md` | **Genérico** | Ponto de partida para qualquer plugin novo — `plugin_php`, widget, `settings_schema`, hooks, manifest. |

## Como usar
1. Abre/edita um plugin no Studio → aba **Chat IA**.
2. Carrega o skill com o botão **✦** e descreve o plugin.
3. O Modo Construção corre os agentes (lógica / interface / configurações) e aplica os campos.
4. Valida, depois **instala no AnimusFlow** (Install no CMS ou Publish no Marketplace).

## Relacionado
- Guia técnico (Sistema A): `animusFlow/skills/animusflow/references/plugin-development.md`
- Hooks: `page.render`, `content.publish`, `admin.sidebar`. Manifest: `animusflow-plugin.json`.
