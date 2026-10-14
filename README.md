# Sistema PetShop - Projeto Faculdade 🐾

Este projeto é um sistema web moderno para gestão de um PetShop, desenvolvido como parte da disciplina de **Software Program Specification** baseada no Manifesto Ágil.

---

## 🚀 Tecnologias Utilizadas
- **Front-end:** HTML5, CSS3 Moderno (Vanilla CSS) e JavaScript interativo (Design responsivo estilo Dashboard com Menu Lateral e Modal PopUP).
- **Back-end:** PHP puro com conexão segura via PDO e transações atômicas.
- **Banco de Dados:** MySQL 8.0 (Relacional com suporte a chaves estrangeiras e integridade referencial com `ON DELETE CASCADE`).
- **Infraestrutura:** Docker e Docker Compose (Containers isolados para Web-Apache, MySQL e phpMyAdmin).

---

## 📦 Entregas e Funcionalidades (Sprints Ágeis)

- ✅ **AC1:** 
  - Infraestrutura com Docker Compose (Apache, PHP 8.2 e MySQL 8.0).
  - Modelagem do banco de dados relacional e script de inicialização (`init.sql`).
  - Cadastro integrado de Tutores (Clientes) e seus respectivos Pets com persistência no MySQL.
  - Listagem básica com busca/filtro e ordenação cronológica.
  - *Vídeo demonstrativo AC1:* [Assistir no Loom](https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b)

- ✅ **AC2:** 
  - **Interface Dashboard:** Reformulação com menu lateral (Sidebar) em tema Dark Navy, ícones intuitivos e navegação fluida entre "Novo Cadastro" e "Pets e Tutores".
  - **Novos Campos do Pet:** Inclusão de **Idade**, **Foto do Pet** (com upload e preview dinâmico instantâneo) e **Preferências / Observações** (cuidados especiais, alergias e comportamento).
  - **PopUP / Modal de Detalhes:** Visualização enriquecida das informações do tutor e pet com efeito de desfoque (*backdrop-filter: blur*).
  - **Edição Direta no PopUP (UPDATE):** Edição rápida sem sair da tela, com transações PDO no banco.
  - **Exclusão com Efeito Cascata (DELETE CASCADE):** Exclusão confirmada via JavaScript que apaga o tutor e remove automaticamente o pet associado no MySQL.
  - **Cards de Métricas no Topo:** Indicadores de Total de Pets, Tutores, Cães e Gatos/Outros.
  - *Vídeo demonstrativo AC2:* [Assistir no Loom](https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f)

- ⏳ **AC3:** 
  - Sistema de Agendamentos de Serviços (Banho, Tosa, Vacinação, Consultas Veterinárias). *(Em andamento)*

- ⏳ **Prova Final:** 
  - Relatórios analíticos e resumo de faturamento + Diagramas UML (Casos de Uso e Classes). *(Planejado)*

---

## 🔗 Links Oficiais do Projeto

- **Repositório GitHub:** [https://github.com/willianmedeiros/petshop-faculdade](https://github.com/willianmedeiros/petshop-faculdade)
- **Board de Tarefas Público (GitHub Projects):** [https://github.com/users/willianmedeiros/projects/1](https://github.com/users/willianmedeiros/projects/1)
- **Vídeo da Entrega AC1:** [https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b](https://www.loom.com/share/493a3cc56a914b8cb72fa84bb153e28b)
- **Vídeo da Entrega AC2:** [https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f](https://www.loom.com/share/260c54e8c8094a90b86042fb952f784f)

---

## 🛠️ Como rodar o projeto localmente

Como o sistema foi montado utilizando Docker, você não precisa instalar o PHP ou o MySQL diretamente na sua máquina. Basta ter o Docker instalado e rodar um único comando:

### Pré-requisitos
- [Docker](https://www.docker.com/) e [Docker Compose](https://docs.docker.com/compose/) instalados.

### Passos para Execução
1. Clone este repositório:
   ```bash
   git clone https://github.com/willianmedeiros/petshop-faculdade.git
   ```
2. Acesse a pasta do projeto:
   ```bash
   cd petshop-faculdade
   ```
3. Suba os containers do Docker em segundo plano:
   ```bash
   docker compose up -d
   ```
4. Acesse o sistema pelo seu navegador:
   - **Aplicação Web:** [http://localhost:8081](http://localhost:8081)
   - **phpMyAdmin:** [http://localhost:8080](http://localhost:8080) *(Usuário: `root` / Senha: `root`)*

*(O banco de dados e as tabelas com os novos campos serão criados automaticamente pelo arquivo `mysql/init.sql` durante a inicialização do container MySQL).*

---

## 👨‍💻 Integrantes do Projeto

* **Willian Benedito Medeiros** - RA: `1110801`
