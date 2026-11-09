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
   - Aba e painel dedicado para cadastro de novos serviços com nome, valor (R$), ícone/emoji (incluindo opção Genérico `🐾`), duração e descrição.
   - Exibição do catálogo de serviços em **cards visuais modernos e responsivos**, contendo ícones nativos, tempo estimado de atendimento, valor padrão alinhado em linha única e botões de ação rápida (*📅 Agendar*, *✏️ Editar* e *🗑️ Excluir*).
   - Modal interativo para edição completa de qualquer serviço já cadastrado (nome, descrição, preço, tempo e ícone).
   - Carregamento dinâmico automático dos serviços e seus respectivos valores no momento de realizar um novo agendamento.

3. **Painel de Agendamentos em 4 Colunas com Drag & Drop (Fluxo Kanban):**
   - Organização visual dos atendimentos em 4 colunas bem definidas:
     - 🔵 **Agendados**
     - 🟡 **Em Andamento**
     - 🟢 **Concluídos**
     - 🔴 **Cancelados**
   - Suporte completo a **Arrastar e Soltar (Drag and Drop nativo)** dos cards entre as colunas, com atualização automática de status via requisição assíncrona (AJAX) e recálculo instantâneo dos contadores de cada coluna.
   - **Regra de edição estrita:** O botão e modal de edição de agendamentos só é permitido e exibido para cards que estão nas colunas **Agendados** e **Em Andamento**; cards nas colunas Concluídos e Cancelados são protegidos contra alteração indevida.
   - Alternância com um clique entre a **Visão em Colunas** e a **Visão em Tabela**.

4. **PopUP Modal de Agendamentos Simplificado & Atrelado:**
   - PopUP modal responsivo com efeito de desfoque (*glassmorphism*) para agendar e editar atendimentos.
   - **Valor atrelado ao serviço:** O campo manual de valor foi removido do PopUP e atrelado diretamente ao catálogo de serviços cadastrado, exibindo badge com valor e duração estimada.
   - **Status gerenciado no fluxo:** Campo de status removido do formulário de agendamento, pois a evolução do atendimento é controlada diretamente pelo arrastar e soltar do Kanban ou pelo seletor inline.
   - Ao editar um agendamento existente, o modal identifica e destaca claramente o pet do atendimento (`✏️ Editar Agendamento — Pet: [Nome do Pet]`).
   - Campo dedicado para **Observações e Recomendações** mantido no PopUP (alergias, cuidados e instruções específicas).

5. **Sistema de Notificações Toast no Canto Inferior Direito:**
   - As notificações de atualização dos cards de agendamento agora aparecem fixadas no **canto inferior direito** (*bottom-right toast*) com animação suave e desaparecem automaticamente após **5 segundos**.
   - Eliminação de alertas estáticos no topo da página, garantindo uma interface limpa, moderna e sem quebra visual durante a operação do Kanban.

## Links:

* **Github:** https://github.com/willianmedeiros/petshop-faculdade
* **Board (GitHub Projects):** https://github.com/users/willianmedeiros/projects/1
* **Vídeo AC1:** https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b
* **Vídeo AC2:** https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f
* **Vídeo AC3:** [Inserir Link do Loom]

## Integrantes do grupo:

* Willian Benedito Medeiros - RA: 1110801
