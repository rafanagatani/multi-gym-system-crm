# Academia System — CRM e gestão multi-academia

Sistema completo de gestão para academias, estúdios e boxes, feito em **PHP + MySQL puro**.
Roda em qualquer hospedagem compartilhada com cPanel: sem Node.js, sem Composer, sem build.
É enviar os arquivos, rodar o instalador e usar.

O sistema é **multi-academia (multi-tenant)**: uma única instalação atende várias academias,
cada uma com seus próprios alunos, planos, equipe, agenda e financeiro, e um painel master
para quem opera a plataforma.

---

## Sumário

- [O que o sistema faz](#o-que-o-sistema-faz)
- [Como funciona](#como-funciona)
- [Módulos em detalhe](#módulos-em-detalhe)
- [Papéis e permissões](#papéis-e-permissões)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Rodando localmente](#rodando-localmente)
- [Arquitetura](#arquitetura)
- [Banco de dados](#banco-de-dados)
- [Segurança](#segurança)
- [Personalização](#personalização)

---

## O que o sistema faz

| Área | Para quem | O que entrega |
|---|---|---|
| **Painel da academia** | Dono, recepção e professores | Alunos, planos, agenda de aulas, check-ins, financeiro, relatórios, leads e equipe |
| **AI Growth** | Dono e gestão | Lista de oportunidades de retenção e cobrança, com a mensagem de WhatsApp pronta |
| **Página pública** | Futuros alunos | Link `/agendar/sua-academia` com aula experimental, trial de 7 dias e planos |
| **Área Master** | Dono da plataforma | Todas as academias, MRR consolidado, criação, suspensão e reset de acesso |

---

## Como funciona

```
Super admin (Área Master)
   └── cria a Academia + o admin dela (senha gerada na hora)
          └── Admin da academia
                ├── cadastra planos, equipe e grade de aulas
                ├── divulga o link público /agendar/slug (com QR Code)
                ├── recebe leads e converte em alunos
                └── registra check-ins, mensalidades e acompanha o AI Growth
```

1. **A plataforma é instalada uma vez.** O instalador cria o banco e o super administrador.
2. **O super admin cadastra cada academia** na Área Master, já com o login do admin dela.
   A academia nasce com um plano da plataforma (`starter`, `pro` ou `premium`), um valor
   mensal e um status (`trial`, `active` ou `blocked`).
3. **O admin da academia configura a casa:** marca, cor, endereço, horários, planos que
   vende, equipe e grade semanal de aulas.
4. **A academia divulga o link público.** Quem acessa escolhe aula experimental, trial de
   7 dias ou um plano e deixa os dados. Isso vira um **lead** no painel.
5. **O lead vira aluno com um clique.** Ao marcar o lead como convertido, a ficha do aluno
   é criada automaticamente.
6. **No dia a dia,** a recepção registra check-ins e dá baixa em mensalidades, e o
   AI Growth aponta quem precisa de atenção.

Cada academia só enxerga os próprios dados: o isolamento por `academy_id` é aplicado em
toda consulta e em toda gravação.

---

## Módulos em detalhe

### Dashboard
Visão do dia: check-ins de hoje e da semana, alunos ativos, faturamento, inadimplência,
aulas de hoje e os alertas do AI Growth.

### Alunos
Cadastro completo (dados pessoais, plano, saúde e contato de emergência), com busca e
filtros por plano, status e situação de pagamento.

### Planos
Planos mensais, trimestrais, anuais ou por número de check-ins. Dá para marcar um plano
como destaque e escolher quais aparecem na página pública.

### Agenda
Grade semanal de aulas com **arrastar e soltar** entre dias e horários, e cores por
modalidade.

### Check-ins
Terminal de balcão com busca por nome ou CPF e atalhos de teclado (`F2`, `Enter`, `Esc`,
`F5`). Alunos bloqueados não passam, e o sistema evita check-in duplicado na mesma hora.

### Financeiro
Receitas, despesas e inadimplência por mês, com baixa de pagamento em um clique.

### Relatórios
Faturamento dos últimos 12 meses, retenção, modalidades e horários de pico. A exportação
em PDF é feita pela impressão do navegador.

### Leads
Recebe os cadastros da página pública e acompanha cada um pelos status `novo`,
`contatado`, `convertido` e `perdido`. Converter um lead cria o aluno automaticamente.

### AI Growth
Analisa os dados reais da academia e monta uma lista de oportunidades. Cada uma vem com
severidade, impacto estimado, a lista de alunos e um modelo de mensagem de WhatsApp já
preenchido. As regras atuais são:

| Oportunidade | Critério |
|---|---|
| Alunos sumidos | Sem check-in há mais de 14 dias |
| Inadimplência | Mensalidades vencidas |
| Trials acabando | Trial termina nos próximos 3 dias |
| Frequência baixa | De 1 a 3 check-ins no mês |
| Aniversariantes | Fazem aniversário no mês |

A análise roda dentro do próprio sistema, por regras sobre o banco de dados. Não depende
de API externa nem envia dados dos alunos para terceiros.

### Equipe
Professores, personal trainers, recepção e admins, com criação de login e permissão por
função.

### Configurações
Marca, cor, logo (por URL), endereço, horários de funcionamento, link público de matrícula e
QR Code (gerado pelo próprio sistema).

### Página pública
`/agendar/sua-academia`, na cor e com a marca da academia. Oferece aula experimental,
trial de 7 dias e os planos ativos. Tem proteção simples contra spam (campo isca e limite
de um envio a cada 30 segundos) e avisa a academia por e-mail quando o envio de e-mails
está ligado.

### Área Master
Painel de quem opera a plataforma: total de academias, ativas, em trial e bloqueadas,
**MRR consolidado** (soma do valor mensal das academias ativas), total de alunos e
crescimento nos últimos 12 meses. Permite criar academia com o admin, resetar a senha do
admin, suspender e reativar. Academias em trial ou suspensas veem uma faixa de aviso no
painel.

---

## Papéis e permissões

| Papel | Acesso |
|---|---|
| `super_admin` | Tudo, em todas as academias (Área Master) |
| `admin` | Tudo dentro da própria academia |
| `recepcao` | Alunos, check-ins, financeiro, relatórios, agenda e planos |
| `professor` | Alunos, check-ins e agenda |

Excluir alunos, editar equipe e alterar configurações são ações exclusivas do `admin`.

---

## Requisitos

- PHP **8.0 ou superior**, com as extensões `pdo_mysql` e `mbstring`
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com `mod_rewrite` (padrão no cPanel)

QR Code, gráficos e ícones são gerados pelo próprio sistema. Não há dependência de CDN
nem de serviço externo.

---

## Instalação

Resumo para cPanel. O passo a passo completo, com subpasta, e-mail, backup e solução de
problemas, está no [LEIA-ME.md](LEIA-ME.md).

1. **Envie os arquivos** para `public_html/` (ou uma subpasta). O `index.php` precisa
   ficar na raiz escolhida.
2. **Crie o banco MySQL** e um usuário com todos os privilégios.
3. **Acesse `https://seudominio.com.br/instalar.php`**, informe os dados do banco e crie
   o super administrador. O instalador cria as tabelas e gera o `config/config.php`.
4. **Apague o `instalar.php`** do servidor depois de concluir.
5. **Entre em `https://seudominio.com.br/entrar`** e cadastre a primeira academia na
   Área Master.

> O arquivo `config/config.php` guarda as credenciais do banco e **não deve ser enviado
> para o repositório**. Use o `config/config.exemplo.php` como modelo.

---

## Rodando localmente

Com PHP e MySQL instalados na máquina:

```bash
git clone https://github.com/rafanagatani/multi-gym-system-crm.git
cd multi-gym-system-crm
php -S localhost:8000
```

Depois acesse `http://localhost:8000/instalar.php` e siga o instalador. Para ver os erros
na tela durante o desenvolvimento, defina `'ambiente' => 'dev'` em `config/config.php`.

---

## Arquitetura

MVC enxuto, sem framework:

- **`index.php` é o front controller.** Toda requisição passa por ele, que carrega a
  configuração, inicia a sessão e resolve a rota numa tabela simples
  (`'GET painel/alunos' => ['AlunosController', 'index']`).
- **Um controller por módulo** em `app/controllers/`.
- **Views em PHP puro** em `app/views/`, com layouts separados para painel, master e
  autenticação.
- **Núcleo em `app/core/`:** `Config`, `Database` (PDO), `Auth`, `QrCode` e `helpers`.

```
├── index.php              Front controller (todas as rotas passam aqui)
├── instalar.php           Instalador — apague depois de instalar
├── .htaccess              URLs amigáveis e proteção das pastas
├── config/
│   └── config.exemplo.php Modelo de configuração
├── sql/schema.sql         Estrutura do banco
├── app/
│   ├── core/              Config, Database, Auth, QrCode, helpers
│   ├── controllers/       Um controller por módulo
│   └── views/
│       ├── layout/        Layouts (painel, master, auth) e partials
│       ├── site/          Página inicial
│       ├── auth/          Login e recuperação de senha
│       ├── painel/        Telas da academia
│       ├── master/        Telas do super admin
│       └── publico/       Página de matrícula
├── assets/                CSS e JS (sem dependência externa)
└── uploads/               Pasta reservada para arquivos enviados (protegida)
```

---

## Banco de dados

| Tabela | Guarda |
|---|---|
| `academy` | Academias: marca, endereço, plano da plataforma, status e trial |
| `usuario` | Logins e papéis de acesso |
| `plan` | Planos que cada academia vende |
| `team_member` | Equipe da academia |
| `student` | Alunos |
| `schedule` | Grade de aulas |
| `checkin` | Registros de check-in |
| `financial` | Receitas e despesas |
| `lead` | Cadastros vindos da página pública |
| `app_config` | Configurações globais da plataforma |

A estrutura completa está em [`sql/schema.sql`](sql/schema.sql).

---

## Segurança

- Senhas com `password_hash` (bcrypt) e re-hash automático
- Token CSRF em todo formulário que grava dados
- Consultas exclusivamente com *prepared statements*
- Saída HTML sempre escapada
- Limite de 6 tentativas de login a cada 10 minutos
- Sessão com cookie `HttpOnly` + `SameSite=Lax` (e `Secure` sob HTTPS)
- Pastas `config/`, `app/` e `sql/` bloqueadas pelo `.htaccess`
- Troca de senha obrigatória no primeiro acesso de contas criadas por terceiros

---

## Personalização

| O quê | Onde |
|---|---|
| Nome do sistema | `config/config.php` → `app.nome` |
| Cores e visual | `assets/css/app.css` → bloco `:root` no topo |
| Textos da página inicial | `app/views/site/inicio.php` |
| Cor e logo de cada academia | Painel → Configurações → Aparência |
