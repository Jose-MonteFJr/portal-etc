<form action="cadastrar_usuario.php" method="POST">
    <!-- Dados comuns -->
    <label>Nome:</label>
    <input type="text" name="nome" required><br>

    <label>Email:</label>
    <input type="email" name="email" required><br>

    <label>Senha:</label>
    <input type="password" name="senha" required><br>

    <label>Tipo de usuário:</label>
    <select name="tipo" id="tipo" required onchange="mostrarCampos()">
        <option value="">Selecione...</option>
        <option value="aluno">Aluno</option>
        <option value="professor">Professor</option>
        <option value="coordenador">Coordenador</option>
    </select><br>

    <!-- Campos específicos -->
    <div id="campos-aluno" style="display:none;">
        <label>Matrícula:</label>
        <input type="text" name="matricula"><br>

        <label>Curso:</label>
        <input type="text" name="curso"><br>
    </div>

    <div id="campos-professor" style="display:none;">
        <label>Área de formação:</label>
        <input type="text" name="area_formacao"><br>
    </div>

    <div id="campos-coordenador" style="display:none;">
        <label>Setor:</label>
        <input type="text" name="setor"><br>
    </div>

    <button type="submit">Cadastrar</button>
</form>

<script>
function mostrarCampos() {
    document.getElementById("campos-aluno").style.display = "none";
    document.getElementById("campos-professor").style.display = "none";
    document.getElementById("campos-coordenador").style.display = "none";

    let tipo = document.getElementById("tipo").value;
    if(tipo === "aluno") {
        document.getElementById("campos-aluno").style.display = "block";
    } else if(tipo === "professor") {
        document.getElementById("campos-professor").style.display = "block";
    } else if(tipo === "coordenador") {
        document.getElementById("campos-coordenador").style.display = "block";
    }
}
</script>