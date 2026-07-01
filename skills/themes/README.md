# Skills de geração de TEMAS (Sistema B)

Prompts dados ao **Chat IA do editor de tema** (AnimusFlowStudio → Temas → editar → Chat IA / Modo
Construção, botão ✦) para gerar um tema completo.

## Skills aqui

| Ficheiro | Tipo | Usar quando |
|----------|------|-------------|
| `theme-creation-skill.md` | **Genérico** | Ponto de partida para qualquer tema novo — contrato `json_updates`, design system, secções, schema `theme_settings`, convenções. |
| `aerospace_theme_skill.md` | **Skill carregável** | Brief de design do tema AeroSpace (~4 KB, cabe no limite de 60 000 do upload ✦). É o que **carregas no Chat IA** para *gerar* o tema. |
| `aerospace_theme_snapshot.md` | **Snapshot / referência** | Tema AeroSpace já resolvido (HTML/CSS/JS literal, espelho da BD, ~120 KB). **Referência** e fonte do `build_aerospace_skill.php --write`; **não** carregar no ✦ (excede o limite). |
| `aerospace-demo-content.json` | **Dados (não-skill)** | Conteúdo demo do AeroSpace (páginas + nav) para **importar no AnimusFlow** quando o tema for instalado. O preview do tema já mostra as 13 `sections` do próprio tema. |

## Como usar
1. Abre/edita um tema no Studio → aba **Chat IA**.
2. Carrega o skill com o botão **✦** (lê-o como instruções) e descreve o tema.
3. O Modo Construção corre os agentes (design / apresentação / negócio / código) e aplica `json_updates`.
4. Valida no **Preview**, depois promove `status` para `published`.
5. Exporta (ZIP ou `.afprompt`) e **instala no AnimusFlow**.

## Guarda de drift (pre-commit)

Existe um hook local em `.git/hooks/pre-commit` que corre
`build_aerospace_skill.php --check` antes de cada commit e **bloqueia** se o
snapshot divergir da BD (se a BD estiver em baixo, apenas avisa). O hook não é
versionado — num clone novo, recria-o a correr o `--check` e bloqueando em
exit code 1 (ver histórico: commits `650e9e6`…`04027bb` diziam "sync theme"
mas iam vazios; este hook impede que volte a acontecer).

Para regenerar o snapshot após editar o tema na BD:
```
php skills/themes/build_aerospace_skill.php --write
```

## Relacionado
- Guia técnico (Sistema A): `animusFlow/skills/animusflow/references/theme-development.md`
- Definições do tema (consumidas no CMS): secção §1a do guia acima + `theme_settings` no `theme.json`.
