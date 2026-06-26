# Skills de geração de TEMAS (Sistema B)

Prompts dados ao **Chat IA do editor de tema** (AnimusFlowStudio → Temas → editar → Chat IA / Modo
Construção, botão ✦) para gerar um tema completo.

## Skills aqui

| Ficheiro | Tipo | Usar quando |
|----------|------|-------------|
| `theme-creation-skill.md` | **Genérico** | Ponto de partida para qualquer tema novo — contrato `json_updates`, design system, secções, schema `theme_settings`, convenções. |
| `aerospace_theme_skill.md` | **Exemplo concreto** | Receita completa do tema AeroSpace (referência de um tema premium real). |

## Como usar
1. Abre/edita um tema no Studio → aba **Chat IA**.
2. Carrega o skill com o botão **✦** (lê-o como instruções) e descreve o tema.
3. O Modo Construção corre os agentes (design / apresentação / negócio / código) e aplica `json_updates`.
4. Valida no **Preview**, depois promove `status` para `published`.
5. Exporta (ZIP ou `.afprompt`) e **instala no AnimusFlow**.

## Relacionado
- Guia técnico (Sistema A): `animusFlow/skills/animusflow/references/theme-development.md`
- Definições do tema (consumidas no CMS): secção §1a do guia acima + `theme_settings` no `theme.json`.
