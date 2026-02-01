# ButterPlan - LifeOS

> Um sistema completo de gestão pessoal: Finanças, Hábitos e Produtividade.

![Status](https://img.shields.io/badge/Status-Concluído-success)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1)

## 📸 Demonstração

<div align="center">
  <img src="ButterPlan\Screenshots.png" alt="Dashboard" width="700">
</div>

## 💡 Sobre o Projeto

O **ButterPlan** nasceu da minha própria necessidade de me organizar tanto financeiramente quanto no auxílio dos meus estudos, eu precisava de uma dashboard inteligente que me ajudasse a ter um controle sobre os meus gastos, e que me lembrasse todos os dias de determinadas tarefas, e que funcionasse como um retrospecto mensal da minha produtividade também, alinhado a idéia em que eu já tinha desenvolvido outros projetos em php, tomei como opção utilizar a mesma linguagem para a criação do projeto, que etualmente eu faço o uso dele, e planejo trazer melhorias no futuro.

### ✨ Funcionalidades Principais

* **💰 Gestão Financeira Completa:**
    * Entradas e Saídas com categorização.
    * **Controle de Contas Fixas:** Lógica automática de saldo livre (Previsão de caixa).
* **✅ Rotina & Tarefas Inteligentes:**
    * **Sistema de Recorrência Avançado:** Tarefas que renascem automaticamente no dia seguinte.
    * **Agendamento por Dias da Semana:** Tarefas que aparecem apenas em dias específicos (ex: Seg e Qua).
    * **Subtarefas (Nested Tasks):** Gestão de projetos complexos com barra de progresso.
    * **Trava de Futuro:** Tarefas agendadas ficam ocultas até o dia correto.
    * 
* **📊 Relatórios e gerenciamento:**
    * Relatórios automáticos de margem de lucro e taxa de produtividade.
    * Dashboard com visão anual e mensal.


## 🚀 Tecnologias Utilizadas

* **Back-end:** PHP 8 (Vanilla - Sem Frameworks)
* **Banco de Dados:** MySQL (Relacional)
* **Front-end:** HTML5, CSS3 (Responsivo), JavaScript (Vanilla)
* **Design Pattern:** MVC Simplificado (Model-View-Controller)

## 🛠️ Como Rodar o Projeto

1.  Clone o repositório:
    ```bash
    git clone (https://github.com/MatheusPBRZ/ButterPlan-Gerenciamento-Pessoal.git)
    ```
2.  Configure o Banco de Dados:
    * Crie um banco chamado `butterplan` no seu MySQL/MariaDB.
    * Importe o arquivo `database.sql` disponível na raiz do projeto.
3.  Configure a conexão:
    * Edite o arquivo `app/Config/Database.php` com suas credenciais locais.
4.  Inicie o servidor (Se usar PHP Built-in server):
    ```bash
    php -S localhost:8080 -t public
    ```
5.  Acesse `http://localhost:8080`

## 🔮 Próximos Passos (Roadmap)

* [ ] Migração do Backend para **Python (Django/FastAPI)**.
* [ ] Desenvolvimento de App Mobile com **React Native**.
* [ ] Integração com APIs de Bancos (Open Finance).

---
Developed by [Matheus Passos](https://github.com/SEU-USUARIO)
