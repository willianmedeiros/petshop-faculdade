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

4. **PopUP Modal de Agendamentos Simplificado & Sincronização de Observações:**
   - PopUP modal responsivo com efeito de desfoque (*glassmorphism*) para agendar e editar atendimentos.
   - **Sincronização de Observações / Recomendações:** Ao selecionar um pet no agendamento, o campo de observações puxa automaticamente as informações médicas cadastradas no perfil do pet. Caso o pet não possuísse observações cadastradas, ao preencher o campo no agendamento, o cadastro do pet na tabela `pets` é atualizado automaticamente.
   - **Valor atrelado ao serviço:** O campo manual de valor foi removido do PopUP e atrelado diretamente ao catálogo de serviços cadastrado, exibindo badge com valor e duração estimada.
   - **Status gerenciado no fluxo:** Campo de status removido do formulário de agendamento, pois a evolução do atendimento é controlada pelo fluxo Kanban.
   - Ao editar um agendamento existente, o modal identifica e destaca claramente o pet do atendimento (`✏️ Editar Agendamento — Pet: [Nome do Pet]`).

5. **Sistema de Notificações Toast no Canto Inferior Direito:**
   - As notificações de atualização dos cards de agendamento agora aparecem fixadas no **canto inferior direito** (*bottom-right toast*) com animação suave e desaparecem automaticamente após **5 segundos**.
   - Eliminação de alertas estáticos no topo da página, garantindo uma interface limpa, moderna e sem quebra visual durante a operação do Kanban.

6. **Arquitetura Modular em Páginas Individuais:**
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
