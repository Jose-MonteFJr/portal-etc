<?php

session_start();
if (isset($_SESSION['id_usuario'])) {
    // Depois que fez o login → redireciona conforme perfil
    if ($_SESSION['tipo'] === 'secretaria') {
        //Se admin/secretaria
        header('Location: admin.php');
        exit;
    } else {
        //Se usuário normal
        header('Location: portal_home.php');
        exit;
    }
}
?>

<!-- LOGIN  -->

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/portal-etc/partials/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/portal-etc/partials/img/portal-etc-logo.png" type="image/png">
    <link rel="stylesheet" href="/portal-etc/partials/css/telaDeLogin.css">
    <title>Login</title>
</head>

<body>
    <div class="wrapper">
        <div class="container main">
            <div class="row">
                <div class="col-md-6 side-image">
                    <div class="text">
                    </div>
                </div>

                <div class="col-md-6 right">

                    <div class="input-box">
                        <header>Portal do Aluno</header>
                        <?php if (!empty($_GET['error'])): ?>
                            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        <form method="post" action="login.php" autocomplete="off">
                            <div class="input-field">
                                <input type="email" name="email" class="input" id="email" required>
                                <label for="email">Email</label>
                            </div>
                            <div class="input-field">
                                <input type="password" name="password" class="input" id="password" required>
                                <label for="password">Senha</label>
                            </div>
                            <div class="input-field">
                                <button type="submit" class="submit">Entrar</button>
                            </div>
                            <div class="signin">
                                <span>Esqueceu a senha? <a href="#">Recuperar</a></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/portal-etc/partials/js/bootstrap.bundle.min.js"></script>
</body>

</html>