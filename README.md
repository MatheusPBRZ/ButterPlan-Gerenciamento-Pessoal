# 🧈 ButterPlan - 

> Um sistema completo de gestão pessoal: Finanças, Hábitos e Produtividade.

![Status](https://img.shields.io/badge/Status-Concluído-success)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1)

## 📸 Demonstração

<div align="center">
  <img src="Screenshots/dashboard.png" width="800" alt="Dashboard Principal"><br><br>

  <img src="Screenshots/financas.png" width="48%" alt="Gestão Financeira e Parcelamentos">
  <img src="Screenshots/tarefas.png" width="48%" alt="Sistema de Tarefas e Cronômetro">
</div>

## 💡 Sobre o Projeto

O **ButterPlan** nasceu da necessidade de centralizar a gestão da vida pessoal em um único lugar, fugindo da complexidade de usar múltiplos apps. Ele foi desenvolvido com foco em performance e lógica de negócios robusta, utilizando PHP puro e SQL otimizado.

### ✨ Funcionalidades Principais

* **💰 Gestão Financeira Completa:**
    * Entradas e Saídas com categorização.
    * **Controle de Parcelamentos:** Histórico de pagamentos, adiantamento de faturas e barra de progresso do item parcelado.
    * **Controle de Contas Fixas:** Lógica automática de pendências no mês e cálculo de saldo livre.
* **✅ Rotina & Tarefas Inteligentes:**
    * **Cronômetro de 24 Horas:** Sistema de expiração em tempo real. Se a tarefa não for feita em 24h, ela expira e é recriada automaticamente.
    * **Agendamento por Dias da Semana:** Tarefas que aparecem apenas nos dias programados (ex: Seg e Qua).
    * **Subtarefas (Nested Tasks):** Gestão de projetos complexos com barra de progresso dinâmica.
    * **Trava de Futuro:** Tarefas agendadas ficam ocultas na aba "Em Andamento" até o dia correto chegar.
* **📊 Business Intelligence Pessoal:**
    * Relatórios automáticos de margem financeira e taxa de produtividade.
    * Dashboard com visão estratégica.

## 🚀 Tecnologias Utilizadas

* **Back-end:** PHP 8 (Vanilla - Sem Frameworks)
* **Banco de Dados:** MySQL (Relacional) com chaves estrangeiras (`ON DELETE CASCADE`)
* **Front-end:** HTML5, CSS3 (Responsivo), JavaScript (Vanilla)
* **Design Pattern:** MVC Simplificado (Model-View-Controller)

## 🛠️ Como Rodar o Projeto

1.  Clone o repositório:
    ```bash
    git clone [https://github.com/SEU-USUARIO/ButterPlan-Gerenciamento-Pessoal.git](https://github.com/SEU-USUARIO/ButterPlan-Gerenciamento-Pessoal.git)
    ```
2.  Configure o Banco de Dados:
    * Crie um banco chamado `butterplan` no seu MySQL/MariaDB.
    * Importe o arquivo `database.sql` disponível na raiz do projeto.
3.  Configure a conexão:
    * Edite o arquivo `app/Config/Database.php` com suas credenciais locais.
4.  Inicie o servidor (Se usar o servidor embutido do PHP):
    ```bash
    php -S localhost:8080 -t public
    ```
5.  Acesse `http://localhost:8080`

## 🔮 Próximos Passos (Roadmap)

* [ ] Migração de partes do sistema para **Python**.
* [ ] Desenvolvimento de App Mobile.
* [ ] Integração com APIs externas.

---

