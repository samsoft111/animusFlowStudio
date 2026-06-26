# Skills de Geração por IA — AnimusFlowStudio (Sistema B)

> [!IMPORTANT]
> **Sistema B — Geração.** Estes ficheiros **não são código nem configuração** do AnimusFlowStudio.
> São **instruções/prompts** dados ao **Chat IA do Studio** (botão ✦ no Modo Construção dos editores
> de tema e plugin) para gerar a estrutura HTML, design system, CSS e comportamento de novos pacotes.
>
> Não confundir com o **Sistema A — Desenvolvimento** (o skill `animusflow` que orienta o agente a
> *construir* a plataforma). Esse vive em `animusFlow/skills/animusflow/`. Ver tabela abaixo.

## Os dois sistemas de skills

| Sistema | Papel | Onde |
|---------|-------|------|
| **A — Dev** | Orienta o agente/Claude a **construir** o AnimusFlow e o AnimusFlowStudio | `animusFlow/skills/animusflow/` (canónico) + `animusFlowStudio/SKILL.md` (apontador) |
| **B — Geração** | Prompts dados ao **Chat IA** para **gerar** temas/plugins/sites para o utilizador | `animusFlowStudio/skills/{themes,plugins}/` · `animusFlow/skills/sites/` |

## Fluxo de criação

```
1. Criar TEMA   no AnimusFlowStudio  → skills/themes/
2. Criar PLUGIN no AnimusFlowStudio  → skills/plugins/
3. Instalar o tema/plugin no AnimusFlow (CMS)
4. Criar o SITE no AnimusFlow         → animusFlow/skills/sites/
```

## Conteúdo desta pasta (Sistema B — Studio)

| Pasta | Gera | Skills |
|-------|------|--------|
| `themes/` | Temas (via editor de tema → Modo Construção) | `theme-creation-skill.md` (genérico) · `aerospace_theme_skill.md` (brief carregável) · `aerospace_theme_snapshot.md` (referência resolvida) |
| `plugins/` | Plugins (via editor de plugin → Modo Construção) | `plugin-creation-skill.md` (genérico) |

> A geração de **site** vive no repo do CMS: `animusFlow/skills/sites/` (porque o site cria-se no
> AnimusFlow depois de instalar os temas/plugins).
