# Academia System

Sistema de gestão para academias em **PHP + MySQL**, feito para rodar em hospedagem
compartilhada com cPanel. Sem Node.js, sem Composer, sem build — é subir os arquivos e usar.

---

## O que tem dentro

| Módulo | O que faz |
|---|---|
| **Dashboard** | Check-ins do dia e da semana, alunos ativos, faturamento, inadimplência, aulas de hoje e alertas da IA |
| **Alunos** | Cadastro completo (dados, plano, saúde, emergência), busca e filtros por plano/status/pagamento |
| **Planos** | Mensal, trimestral, anual, por número de check-ins; plano em destaque e ativação no link público |
| **Agenda** | Grade semanal com **arrastar e soltar** entre dias e horários, cores por modalidade |
| **Check-ins** | Terminal de balcão com busca por nome ou CPF e atalhos `F2`, `Enter`, `Esc`, `F5` |
| **Financeiro** | Receitas, despesas e inadimplência por mês, com baixa de pagamento em um clique |
| **Relatórios** | Faturamento 12 meses, retenção, modalidades, horários de pico — com exportação em PDF pela impressão |
| **Leads** | Cadastros vindos do link público; converte lead em aluno automaticamente |
| **AI Growth** | Detecta quem sumiu, quem atrasou, trials vencendo, frequência baixa e aniversariantes — com a mensagem de WhatsApp pronta |
| **Equipe** | Professores, recepção e admins, com criação de login e permissão por função |
| **Configurações** | Marca, endereço, horários, link público de matrícula e QR Code |
| **Área Master** | Painel do dono da plataforma: todas as academias, MRR consolidado, criação de academia + admin, reset de senha e suspensão |
| **Página pública** | `/agendar/sua-academia` — aula experimental, trial de 7 dias e planos, na cor da academia |

---

## Requisitos

- PHP **8.0 ou superior** (testado em 8.5) com as extensões `pdo_mysql` e `mbstring`
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com `mod_rewrite` (padrão no cPanel)

Nada além disso. O QR Code, os gráficos e os ícones são gerados pelo próprio sistema —
não há dependência de CDN nem de serviço externo.

---

## Instalação no cPanel

### 1. Enviar os arquivos

No **Gerenciador de Arquivos**, envie o conteúdo desta pasta para:

- `public_html/` — se o sistema for rodar no domínio principal
- `public_html/academia/` — se for rodar numa subpasta

> Envie o **conteúdo** da pasta (o `index.php` precisa ficar na raiz escolhida),
> não a pasta `academia-system` inteira.

### 2. Criar o banco de dados

Em **Bancos de dados MySQL**:

1. Crie um banco (ex.: `conta_academia`)
2. Crie um usuário e defina uma senha
3. Associe o usuário ao banco com **TODOS OS PRIVILÉGIOS**

Anote os três dados — o cPanel adiciona o prefixo da sua conta aos nomes.

### 3. Rodar o instalador

Acesse no navegador:

```
https://seudominio.com.br/instalar.php
```

Preencha os dados do banco e crie o **super administrador**. O instalador cria as
tabelas e gera o arquivo `config/config.php` sozinho.

### 4. Apagar o instalador

Depois que aparecer "Instalação concluída", **apague o arquivo `instalar.php`** do
servidor. O sistema avisa na área Master enquanto ele existir.

### 5. Entrar

```
https://seudominio.com.br/entrar
```

Use o e-mail e a senha do super administrador. Você cai na **Área Master**, onde
cadastra a primeira academia e o admin dela.

---

## Instalação numa subpasta

Se o sistema não estiver na raiz do domínio, abra o `.htaccess` e descomente a linha
`RewriteBase`, ajustando para o caminho:

```apache
RewriteBase /academia
```

E em `config/config.php`, preencha a URL completa:

```php
'url' => 'https://seudominio.com.br/academia',
```

---

## Como funciona no dia a dia

```
Super admin (Área Master)
   └── cria a Academia + o admin dela (senha gerada na hora)
          └── Admin da academia
                ├── cadastra planos, equipe e grade de aulas
                ├── divulga o link público /agendar/slug (com QR Code)
                ├── recebe leads e converte em alunos
                └── registra check-ins, mensalidades e acompanha a IA
```

### Papéis de acesso

| Papel | Pode |
|---|---|
| `super_admin` | Tudo, em todas as academias |
| `admin` | Tudo dentro da própria academia |
| `recepcao` | Alunos, check-ins, financeiro, agenda, planos |
| `professor` | Alunos, check-ins, agenda |

Cada academia só enxerga os próprios dados — o isolamento é aplicado em toda
consulta e em toda gravação.

---

## Ativar o envio de e-mails

Por padrão o envio vem desligado, e as senhas geradas aparecem na tela.
Para ligar:

1. No cPanel, crie uma conta de e-mail (ex.: `nao-responda@seudominio.com.br`)
2. Abra `config/config.php`
3. Ajuste a seção `email`:

```php
'email' => [
    'ativo'     => true,
    'remetente' => 'nao-responda@seudominio.com.br',
    'nome_from' => 'Academia System',
],
```

Passam a sair por e-mail: recuperação de senha, credenciais de novos acessos e
aviso de lead novo.

---

## Personalizar

| O quê | Onde |
|---|---|
| Nome do sistema | `config/config.php` → `app.nome` |
| Cores e visual | `assets/css/app.css` → bloco `:root` no topo |
| Textos da página inicial | `app/views/site/inicio.php` |
| Cor e logo de cada academia | Painel → Configurações → Aparência |

---

## Estrutura dos arquivos

```
├── index.php              Front controller (todas as rotas passam aqui)
├── instalar.php           Instalador — APAGUE depois de instalar
├── .htaccess              URLs amigáveis e proteção das pastas
├── config/
│   ├── config.php         Gerado pelo instalador (fora do acesso público)
│   └── config.exemplo.php Modelo, caso prefira configurar na mão
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
└── assets/                CSS e JS (sem dependência externa)
```

---

## Segurança aplicada

- Senhas com `password_hash` (bcrypt) e re-hash automático
- Token CSRF em todo formulário que grava dados
- Consultas exclusivamente com *prepared statements*
- Saída HTML sempre escapada
- Limite de 6 tentativas de login por 10 minutos
- Sessão com cookie `HttpOnly` + `SameSite=Lax` (e `Secure` sob HTTPS)
- Pastas `config/`, `app/` e `sql/` bloqueadas pelo `.htaccess`
- Troca de senha obrigatória no primeiro acesso de contas criadas por terceiros

---

## Backup

Faça backup de duas coisas:

1. **Banco de dados** — cPanel → *Backup* → *Baixar backup do banco MySQL*
2. **`config/config.php`** — guarda as credenciais de conexão

Os arquivos do sistema podem ser reenviados a qualquer momento.

---

## Problemas comuns

**Erro 500 ou página em branco**
Verifique a versão do PHP em *Selecionar versão do PHP* (precisa ser 8.0+) e se
`pdo_mysql` e `mbstring` estão marcadas.

**"Não foi possível conectar ao banco de dados"**
Confira `config/config.php`. No cPanel, o nome do banco e do usuário levam o
prefixo da conta (ex.: `minhaconta_academia`).

**Só a página inicial abre; as outras dão 404**
O `mod_rewrite` não está ativo ou o `.htaccess` não subiu. Confirme que o arquivo
está na raiz (ele começa com ponto e pode estar oculto no gerenciador de arquivos).

**O CSS não carrega**
A pasta `assets/` não foi enviada, ou a `url` em `config/config.php` está diferente
do endereço real do site.
