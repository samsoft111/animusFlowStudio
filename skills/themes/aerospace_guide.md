# Guia de Configuração e Operação — Tema AeroSpace

Este guia fornece instruções passo a passo para instalar, configurar e operar o tema **AeroSpace** no seu site **AnimusFlow**. O AeroSpace é um tema premium de alta fidelidade com estética de centro de comando aeroespacial (HUD), navegação orbital tridimensional, telemetria em tempo real e comandos por voz.

---

## 1. Fluxo Rápido: Como Usar no seu Site Final
Para levantar o site AeroSpace completo e idêntico ao preview em menos de 1 minuto, siga estes 4 passos:

1.  **Instalação**:
    *   *Via ZIP*: Aceda a **Extensões** ➔ **Temas** ➔ **Carregar Tema (Upload ZIP)**. Selecione o ficheiro `aerospace.zip` e clique em **Instalar**.
    *   *Via Prompt*: Aceda a **Extensões** ➔ **Temas** ➔ **Importar Prompt**. Cole o conteúdo do ficheiro `aerospace.afprompt` e clique em **Importar**.
2.  **Ativação**:
    *   Na lista de temas, localize o **AeroSpace** e clique em **Ativar**.
3.  **Importação de Conteúdo Demo**:
    *   No card do tema **AeroSpace**, clique no botão **Demo** (ícone Sparkles ✨) e confirme. Isto irá criar automaticamente as **5 páginas** (`home`, `sobre`, `servicos`, `galeria`, `contactos`), recheá-las com os blocos/secções e configurar o menu de navegação.
4.  **Definições Recomendadas**:
    *   Clique em **Configurações** no card do tema (ou vá a *Definições do Site*).
    *   Clique no botão **"Repor definições recomendadas"** no topo da página para carregar as cores ciano/escuras, tipografia Outfit/Inter e efeitos HUD de fábrica.
    *   Clique em **Guardar Alterações**.

---

## 3. Guia de Configuração Passo a Passo (CMS)

Aceda a **Definições do Site** no painel de administração para personalizar os seguintes grupos:

### A. Geral & Cabeçalho
*   **Tipo de Logótipo**: Escolha entre "Imagem e Texto", "Apenas Imagem" ou "Apenas Texto". Recomenda-se **Imagem e Texto** para melhor reconhecimento de marca.
*   **Logótipo**: Faça upload do seu logótipo em formato SVG ou PNG transparente. A altura ideal de exibição é entre **32px e 48px** (ajustável no campo *Altura do Logótipo*).
*   **Estilo de Cabeçalho**: Recomenda-se o modo **"Glass / Blur"** para manter o efeito de transparência aeroespacial futurista.
*   **Cabeçalho Fixo (Sticky)**: Mantenha ativado para que o menu de navegação acompanhe o scroll do utilizador.

### B. Navegação Circular Orbital (Radar Central)
Esta é a funcionalidade de assinatura do AeroSpace. É exibida no topo da página inicial.
*   **Estilo do Menu**: Certifique-se de que está selecionado **"Circular Orbital"**.
*   **Hub Central (Home)**: Configure o texto principal (ex: `HOME`) e a legenda (ex: `Central Hub`) que ficam no círculo brilhante central.
*   **Configuração de Satélites**: Pode configurar até 4 botões orbitando ao redor do centro. Para cada um deles:
    1. Defina um **ícone** (Emoji ou unicode, ex: `🛸`, `🖼️`, `📡`, `🌐`).
    2. Adicione uma **descrição curta** (ex: *Operações Aéreas*).
    3. Defina a **cor de acento** individual do satélite para criar contrastes de radar.
*   *Nota para Mobile*: Em telemóveis, o menu encolhe para `320px` e os satélites aproximam-se do centro automaticamente. Os submenus abrem com um toque físico (Touch UX).

### C. Fundo HUD & Screensaver (Ecrã de Boot)
Controla o fundo imersivo tridimensional da página inicial:
*   **Tipo de Fundo**: Escolha entre **Vídeo**, **Foto única** ou **Galeria de fotos**.
*   **Vídeo de Fundo**: Se escolheu vídeo, carregue um ficheiro MP4 em loop (recomendado menos de **5MB** e sem faixa de áudio).
*   **Painel Central (Mensagem)**: Configure o título de boas-vindas (ex: `AEROSPACE`), tamanho e cor, além da instrução de desbloqueio (ex: *Passe o cursor ou toque no ecrã para aceder*).
*   **Opacidade e Blur**: Ajuste o nível de escurecimento e desfoque do fundo para garantir que as informações de telemetria ficam perfeitamente legíveis.

### D. Galeria de Fotos
A secção de galeria suporta 3 layouts altamente interativos:
*   **Layout da Galeria**:
    *   **Carrossel 3D**: Exibe as imagens numa órbita 3D rotativa. **Ideal para 3 a 12 imagens**.
    *   **Mosaico (Masonry)** ou **Grelha (Grid)**: Ideais para **dezenas de imagens** pois utilizam *lazy-loading* (carregamento dinâmico sob scroll) para não abrandar o site.
*   **Raio da Órbita 3D**: Se tiver mais de 8 ou 10 imagens no Carrossel 3D, aumente o valor do raio (até 150%) para afastar as imagens e evitar que se sobreponham na rotação.
*   **Efeitos Premium**:
    *   *Varredura HUD*: Ative para aplicar uma grelha de linhas de scan científicas sobre as fotos.
    *   *Efeito de Zoom*: Ative para aplicar um aumento suave e brilho ao passar o cursor.
    *   *Rotação por Arrasto*: Permite arrastar com o rato ou deslizar o dedo (swipe) para rodar as fotos 3D com física de inércia.
    *   *Efeitos Sonoros*: Toca pequenos bipes de transição e um som de sonar ao abrir fotos amplificadas (Lightbox).

### E. Funcionalidades Científicas (HUD)
*   **Painel de Telemetria Live**: Ative para exibir no canto do ecrã um painel de telemetria flutuante. O JavaScript fará flutuar os valores de altitude, velocidade do vento e bateria em tempo real.
*   **Comandos por Voz**: Ative para permitir ao utilizador falar diretamente com o site. Diga *"sobre"*, *"serviços"*, *"telemetria"*, *"chat"* ou *"fechar"* para navegar de forma autónoma.
*   **Preloader de Consola**: Ative para mostrar uma simulação de consola de programação (booting) no primeiro carregamento do site.

---

## 4. Edição de Conteúdo (Page Builder Visual)
Depois de configurar as definições globais, pode editar o conteúdo das secções individualmente:
1. Vá ao **Editor Visual** da página no AnimusFlow.
2. Clique sobre qualquer bloco de secção (ex: **About**, **Stats**, **Team** ou **Testemunhos**).
3. **Alterar Textos**: Clique diretamente sobre os títulos ou parágrafos para reescrever o conteúdo.
4. **Substituir Imagens**: Use o cartão de propriedades lateral do bloco para carregar novas imagens das suas missões.
5. **Formulário de Contacto**: No bloco de contactos, pode alterar a lista de serviços do dropdown escrevendo um serviço por linha no painel lateral de propriedades do bloco.

---

## 5. Resolução de Problemas (Troubleshooting)

*   **O vídeo de fundo do HUD não carrega no telemóvel:**
    *   *Causa*: Muitos sistemas operativos móveis (iOS/Android) bloqueiam a reprodução automática de vídeos se tiverem áudio ou estiverem em modo de poupança de bateria.
    *   *Solução*: Certifique-se de que o vídeo carregado não tem faixa de áudio (remova-a num editor de vídeo) e que a opção de poupança de energia do telemóvel está desligada.
*   **As imagens do Carrossel 3D estão muito coladas ou sobrepostas:**
    *   *Solução*: Vá a *Definições do Site* ➔ *Galeria* e aumente o **Raio da Órbita 3D** para `120%` ou `130%`.
*   **Desejo silenciar os efeitos sonoros de clique do site:**
    *   *Solução*: O utilizador pode clicar no botão de som (ícone de altifalante) no canto superior direito do cabeçalho. Alternativamente, pode desativar os bipes globalmente desativando o campo *Efeitos sonoros no hover* e *Galeria — efeitos sonoros dedicados* no painel de administração.
*   **O site está desalinhado ou as cores estão incorretas:**
    *   *Solução*: Aceda a *Definições do Site* e clique em *Repor definições recomendadas*. Isto restaurará todos os tokens de design para o padrão testado.
