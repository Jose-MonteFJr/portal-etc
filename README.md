# PORTAL ETC
TCC da escola técnica de ceilândia

## Requisitos
- PHP 8+ (XAMPP ou similar)
- MySQL/MariaDB
- Navegador

## Instalação
1. Importe o arquivo `portal_etc.sql` no phpMyAdmin (ou via CLI).
2. Edite `config/db.php` com seu usuário/senha do MySQL se necessário.
3. Copie a pasta para `C:\xampp\htdocs\portal-etc` (Windows) ou `htdocs` equivalente.
4. Acesse `http://localhost/portal-etc/`.

### Usuários de teste
- Admin: **jose@admin.com** / **admin123**
> As senhas já estão com `password_hash()` no SQL de seed.

## Estrutura
```
portal-etc/
├─ index.php         # Tela de login
├─ login.php         # Processa o login
├─ logout.php        # Encerra sessão
├─ admin.php         # Dashboard do Admin (lista usuários)
├─ portal_home.php          # Página do usuário padrão (nome no topo)
├─ protect.php       # Middleware simples de autenticação e autorização
├─ config/
│  └─ db.php         # Conexão PDO
└─ partials/
   ├─ header.php     # Navbar Bootstrap
   └─ footer.php
```

## Notas importantes!
- Usa `password_hash()` e `password_verify()`.
- Sessões PHP para manter o usuário logado.
- Redirecionamento por perfil (Admin → `admin.php`, User → `portal_home.php`).

## CRUD para Admin
- **Criar usuário**: `users_create.php`
- **Editar usuário**: `users_edit.php`
- **Excluir usuário**: `users_delete.php` (POST + CSRF)
- Protegido por `ensure_admin()` em `helpers.php`.

### Segurança de código aplicada
- `password_hash()` / `password_verify()`
- **CSRF token** em formulários sensíveis
- Validação e tratamento de erros (e-mail único, senha mínima)
- Bloqueio para excluir o próprio usuário logado