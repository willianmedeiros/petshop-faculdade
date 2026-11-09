# Sistema PetShop - Entrega AC3

Este projeto foi feito para a disciplina de **Software Program Specification** baseada no Manifesto Ágil.

O projeto consiste num sistema web moderno para a gestão de um PetShop, estruturado com back-end em PHP (PDO), front-end clássico responsivo (HTML, CSS e JavaScript Vanilla) e banco de dados relacional (MySQL).

A entrega desta terceira fase (AC3) foca na implementação do **Módulo Completo de Agendamento e Gestão de Serviços**, permitindo o controle de fluxo de atendimento por colunas e catálogo integrado de serviços com preços dinâmicos.

## 🚀 Novas Funcionalidades Desenvolvidas na AC3:

1. **Modelagem de Dados e Relacionamentos no MySQL:**
   - Criação da tabela `servicos` para catálogo de serviços, preços padrão, ícones e tempos estimados de atendimento.
   - Criação da tabela `agendamentos` com chaves estrangeiras para `pets` (`ON DELETE CASCADE`) e para `servicos` (`ON DELETE SET NULL`).
   - Atualização completa do script de inicialização do Docker (`mysql/init.sql`).

2. **Área de Catálogo de Serviços & Preços (Cards Responsivos & Edição):**
   - Cadastro e gestão de novos serviços com nome, valor (R$), ícone/emoji (incluindo opção Genérico `🐾`), duração e descrição.
   - Exibição do catálogo de serviços em **cards visuais modernos e responsivos**, contendo ícones nativos, tempo estimado de atendimento, valor padrão alinhado em linha única e botões de ação rápida (*Agendar*, *✏️ Editar* e *🗑️ Excluir*) com espaçamento otimizado.
   - Modal interativo para edição completa de qualquer serviço já cadastrado (nome, descrição, preço, tempo e ícone).
   - Carregamento dinâmico automático dos serviços e seus respectivos valores no momento de realizar um novo agendamento.

3. **Painel de Agendamentos em 4 Colunas com Drag & Drop (Fluxo Kanban):**
   - Organização visual dos atendimentos em 4 colunas bem definidas:
     - 🔵 **Agendados**
     - 🟡 **Em Andamento**
     - 🟢 **Concluídos**
     - 🔴 **Cancelados**
   - Suporte completo a **Arrastar e Soltar (Drag and Drop nativo)** dos cards entre as colunas, com atualização automática de status via requisição assíncrona (AJAX) e recálculo instantâneo dos contadores de cada coluna.
   - **Regra de edição estrita e dinâmica:** O botão e modal de edição só ficam disponíveis para cards em **Agendados** e **Em Andamento**. Caso um card em Concluído seja retornado para Em Andamento ou Agendado, a permissão e o botão de edição são reativados automaticamente.
   - **Irreversibilidade e Bloqueio de Cancelados:** Cards movidos para a coluna **Cancelados** ficam estilizados em tom cinza desbotado, têm o arrasto desabilitado (`cursor: not-allowed`) e não podem ser movidos para nenhuma etapa anterior nem editados.
   - **PopUp de Confirmação para Cancelamento:** Ao arrastar para a coluna Cancelados ou selecionar o status Cancelado na tabela, um PopUp modal de confirmação é acionado alertando sobre o caráter irreversível da ação.
   - **Interface Limpa:** O seletor inline de status foi retirado dos cards das colunas (pois a movimentação ocorre via Drag & Drop e botões rápidos) e mantido exclusivamente na visualização em Tabela.
   - Alternância com um clique entre a **Visão em Colunas** e a **Visão em Tabela**.

4. **PopUP Modal de Agendamentos com Observações Duais (Ficha Permanente vs Atendimento Específico):**
   - PopUP modal responsivo com efeito de desfoque (*glassmorphism*) para agendar e editar atendimentos.
   - **Gestão Inteligente de Observações / Recomendações:**
     - **Item Lista no Cadastro do Pet:** No cadastro do animal (`cadastro.php`) e na edição (`pets.php`), as observações/recomendações clínicas são gerenciadas como uma lista dinâmica de itens com sugestões rápidas em um clique (orelhas, alergias, patas, secador, medicação), formatadas de forma padronizada.
     - **Dupla Exibição no Agendamento:** Ao selecionar o Pet para um atendimento, o modal carrega a lista da ficha permanente do Pet em destaque **amarelo clarinho** (`⭐ Ficha Permanente`) e permite registrar uma nova observação específica para aquele atendimento em destaque **azul** (`📌 Observação Específica deste Agendamento`).
     - **Preservação do Histórico Permanente:** A observação informada no agendamento é gravada exclusivamente para aquele serviço (`agendamentos.observacoes`), garantindo que observações pontuais de um determinado dia não alterem nem sobrescrevam a ficha permanente do animal.
     - **Visualização em Cards e Tabela:** Ambos os tipos de observação são exibidos de forma clara e diferenciada nos cards do Kanban e nas linhas da Tabela (Ficha em amarelo clarinho e Atendimento em azul).
   - **Valor atrelado ao serviço:** O campo manual de valor foi removido do PopUP e atrelado diretamente ao catálogo de serviços cadastrado, exibindo badge com valor e duração estimada.
   - **Status gerenciado no fluxo:** Campo de status removido do formulário de agendamento, pois a evolução do atendimento é controlada pelo fluxo Kanban.
   - Ao editar um agendamento existente, o modal identifica e destaca claramente o pet do atendimento (`✏️ Editar Agendamento — Pet: [Nome do Pet]`).

5. **Redesign Visual Completo da Ficha Cadastral do Pet & Tutor (PopUp de Detalhes):**
   - Total reformulação visual do PopUp de detalhes do pet (`modalDetalhes` em `pets.php`), resolvendo desorganizações visuais anteriores:
     - **Hero Banner Gradiente:** Apresenta o avatar redondo do animal com borda contrastante e sombra, nome em destaque, identificador `#PET-{id}`, chips visuais para espécie, raça, idade e badge de status "✓ Ativo".
     - **Grid de Cartões Estruturados:** Divisão em duas seções elegantes lado a lado — *Tutor Responsável* (Nome Completo, WhatsApp em destaque azul e E-mail) e *Dados de Registro* (Nome Oficial, Espécie/Raça e Data de Cadastro).
     - **Card Clínico em Amarelo Clarinho:** Seção dedicada para *Observações & Recomendações Clínicas* formatadas como lista com marcadores de estrelas (`⭐`), garantindo visual limpo e legível.
     - **Ações Alinhadas:** Botões estilizados de *Excluir Cadastro* (em tom suave de perigo) e *Editar Informações* (botão vibrante primário) com transição suave para o modo de edição integrado.

6. **Sistema de Notificações Toast no Canto Inferior Direito:**
   - As notificações de atualização dos cards de agendamento agora aparecem fixadas no **canto inferior direito** (*bottom-right toast*) com animação suave e desaparecem automaticamente após **5 segundos**.
   - Eliminação de alertas estáticos no topo da página, garantindo uma interface limpa, moderna e sem quebra visual durante a operação do Kanban.

7. **Arquitetura Modular em Páginas Individuais:**
   - Transição da antiga navegação baseada em abas para páginas dedicadas e independentes, melhorando a manutenibilidade, performance e organização do sistema:
     - 📊 **Dashboard Geral (`dashboard.php`):** Visão executiva com métricas e KPIs consolidados, atalhos para ações rápidas, próximos agendamentos e catálogo rápido.
     - 🐶 **Pets & Tutores (`pets.php`):** Gerenciamento completo de pacientes, filtros interativos por espécie (Cães, Gatos, Outros), busca em tempo real e modal de detalhes.
     - ➕ **Novo Cadastro (`cadastro.php`):** Formulário integrado de cadastro de tutor e pet com preview em tempo real da foto.
     - 📅 **Agendamentos (`agendamentos.php`):** Painel operacional Kanban em 4 colunas com Drag & Drop e visualização em tabela.
     - 🏷️ **Serviços & Preços (`servicos.php`):** Catálogo interativo em cards visuais, formulário de novos serviços e modal de edição.
   - Modularização com inclusão de componentes reutilizáveis (`src/includes/header.php`, `src/includes/footer.php` e `src/includes/functions.php`).
   - Roteador inteligente em `src/index.php` com redirecionamento automático para `dashboard.php` mantendo compatibilidade retroativa para links antigos com parâmetro `?tab=`.

## Links:

* **Github:** https://github.com/willianmedeiros/petshop-faculdade
* **Board (GitHub Projects):** https://github.com/users/willianmedeiros/projects/1
* **Vídeo AC1:** https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b
* **Vídeo AC2:** https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f
* **Vídeo AC3:** [Inserir Link do Loom]

## Integrantes do grupo:

* Willian Benedito Medeiros - RA: 1110801
