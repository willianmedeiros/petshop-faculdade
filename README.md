# Sistema PetShop - Projeto Faculdade 🐾

Este projeto é um sistema web para gestão de um PetShop, desenvolvido como parte da disciplina de **Software Program Specification** baseada no Manifesto Ágil.

## 🚀 Tecnologias Utilizadas
- **Front-end:** HTML5 e CSS3 Vanilla (Design moderno e responsivo).
- **Back-end:** PHP puro com conexão via PDO.
- **Banco de Dados:** MySQL 8.0.
- **Infraestrutura:** Docker e Docker Compose.

## 📦 Entregas e Funcionalidades (Sprints)

- ✅ **AC1:** Infraestrutura (Docker + Banco) e Cadastro Integrado de Tutores e Pets. Listagem básica.
- ✅ **AC2:** Tela de Detalhes, Edição (Update) e Exclusão (Delete) de cadastros com efeito cascata (CASCADE) no banco. (CRUD Completo).
- ⏳ **AC3:** Sistema de Agendamentos de Serviços (Banho, Tosa, Consulta). *(A fazer)*
- ⏳ **Prova:** Dashboard analítico e resumo financeiro + Diagramas de Caso de Uso e Classes. *(A fazer)*

## 🛠️ Como rodar o projeto localmente

Como o sistema foi montado utilizando Docker, você não precisa instalar o PHP ou o MySQL diretamente na sua máquina. Basta ter o Docker instalado e rodar um único comando.

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
3. Suba os containers do Docker em background:
   ```bash
   docker-compose up -d
   ```
4. Acesse o sistema pelo seu navegador:
   **http://localhost:8081**

*(O banco de dados e as tabelas serão criados automaticamente pelo arquivo `init.sql` durante a inicialização do container MySQL).*

---
**Desenvolvido por Willian Medeiros.**
