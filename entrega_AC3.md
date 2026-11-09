# Sistema PetShop - Entrega AC3

Este projeto foi feito para a disciplina de **Software Program Specification** baseada no Manifesto Ágil.

O projeto consiste num sistema web moderno para a gestão de um PetShop, estruturado com back-end em PHP (PDO), front-end clássico responsivo (HTML, CSS e JavaScript Vanilla) e banco de dados relacional (MySQL).

A entrega desta terceira fase (AC3) foca na implementação do **Módulo Completo de Agendamento e Gestão de Serviços**, permitindo o controle de fluxo de atendimento por colunas e catálogo integrado de serviços com preços dinâmicos.

## 🚀 Novas Funcionalidades Desenvolvidas na AC3:

1. **Modelagem de Dados e Relacionamentos no MySQL:**
   - Criação da tabela `servicos` para catálogo de serviços, preços padrão, ícones e tempos estimados de atendimento.
   - Criação da tabela `agendamentos` com chaves estrangeiras para `pets` (`ON DELETE CASCADE`) e para `servicos` (`ON DELETE SET NULL`).
   - Atualização completa do script de inicialização do Docker (`mysql/init.sql`).

2. **Área de Catálogo de Serviços & Preços:**
   - Aba e painel dedicado para cadastro de novos serviços com nome, valor (R$), ícone/emoji, duração e descrição.
   - Carregamento dinâmico automático dos serviços e seus respectivos valores no momento de realizar um novo agendamento.

3. **Painel de Agendamentos em 4 Colunas (Fluxo Kanban):**
   - Organização visual dos atendimentos em 4 colunas bem definidas:
     - 🔵 **Agendados**
     - 🟡 **Em Andamento**
     - 🟢 **Concluídos**
     - 🔴 **Cancelados**
   - Alteração interativa de status diretamente na visualização conforme a evolução do atendimento do pet (via seletor inline ou botões de avanço rápido).
   - Alternância com um clique entre a **Visão em Colunas** e a **Visão em Tabela**.

4. **PopUP Modal de Agendamentos:**
   - PopUP modal responsivo com efeito de desfoque (*glassmorphism*) para agendar e editar atendimentos.
   - Carregamento instantâneo do valor ao selecionar o serviço via JavaScript.
   - Campo dedicado para **Observações e Recomendações** mantido no PopUP (alergias, cuidados e instruções específicas).

## Links:

* **Github:** https://github.com/willianmedeiros/petshop-faculdade
* **Board (GitHub Projects):** https://github.com/users/willianmedeiros/projects/1
* **Vídeo AC1:** https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b
* **Vídeo AC2:** https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f
* **Vídeo AC3:** [Inserir Link do Loom]

## Integrantes do grupo:

* Willian Benedito Medeiros - RA: 1110801
