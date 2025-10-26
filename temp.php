
<div class="d-flex align-items-center justify-content-between mb-3">
  <h2 class="h4 mb-0">Editar Usuário #<?php echo (int)$user['id_usuario']; ?></h2>
  <a class="btn btn-outline-secondary btn-sm" href="admin.php">Voltar</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Formulario  -->

<form method="post">
  <?php csrf_input(); ?>
  <!-- CAMPOS USUARIO -->
  <div class="card shadow-sm p-3">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="nome_completo">Nome completo: </label>
        <input type="text" id="nome_completo" name="nome_completo" maxlength="150" class="form-control" placeholder="Digite o nome" value="<?php echo htmlspecialchars($nome_completo); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="cpf">Cpf: </label>
        <input type="text" id="cpf" name="cpf" maxlength="14" class="form-control" placeholder="XXX.XXX.XXX-XX" value="<?php echo htmlspecialchars($cpf); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="email">E-mail: </label>
        <input type="email" id="email" name="email" maxlength="150" class="form-control" placeholder="exemplo@exemplo.com" value="<?php echo htmlspecialchars($email); ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="telefone">Telefone: </label>
        <input type="tel" id="telefone" name="telefone" maxlength="20" class="form-control" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($telefone); ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="data_nascimento">Data de nascimento: </label>
        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="status">Status: </label>
        <select name="status" id="status" class="form-select">
          <option value="ativo" <?php echo $status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
          <option value="inativo" <?php echo $status === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
        </select>
      </div>
      <!-- SELECT PERFIL -->
      <div class="col-md-3">
        <label class="form-label" for="perfil">Perfil: </label>
        <select name="tipo" id="perfil" class="form-select" onchange="mostrarCampos()" <?php echo $id_usuario ? "disabled" : ""; ?>>
          <option value="aluno" <?php echo $tipo === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
          <option value="secretaria" <?php echo $tipo === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
          <option value="professor" <?php echo $tipo === 'professor' ? 'selected' : ''; ?>>Professor</option>
          <option value="coordenador" <?php echo $tipo === 'coordenador' ? 'selected' : ''; ?>>Coordenador</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="password">Nova senha: (Opcional) </label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Deixe em branco para manter">
      </div>


      <!-- Campo aluno específico -->
      <div id="campos-aluno" style="<?php echo $tipo === 'aluno' ? 'display:block;' : 'display:none;'; ?>" class="row g-3 mt-3">
        <?php if ($tem_turmas): ?>
          <div class="col-md-6">
            <label class="form-label" for="turma">Turma:</label>
            <select id="turma" name="id_turma" class="form-select">
              <option value="">Selecione</option>
              <?php foreach ($turmas as $turma): ?>
                <option value="<?php echo $turma['id_turma']; ?>" <?php echo ($id_turma == $turma['id_turma']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($turma['nome'] . " - " . $turma['ano'] .  " - " . $turma['semestre'] . "º Semestre - " . $turma['turno']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="data_ingresso">Data de ingresso:</label>
            <input type="date" id="data_ingresso" name="data_ingresso" class="form-control" value="<?php echo htmlspecialchars($data_ingresso); ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="status_academico">Status acadêmico: </label>
            <select name="status_academico" id="status_academico" class="form-select">
              <option value="cursando" <?php echo $status_academico === 'cursando' ? 'selected' : ''; ?>>Cursando</option>
              <option value="formado" <?php echo $status_academico === 'formado' ? 'selected' : ''; ?>>Formado</option>
              <option value="trancado" <?php echo $status_academico === 'trancado' ? 'selected' : ''; ?>>Trancado</option>
              <option value="desistente" <?php echo $status_academico === 'desistente' ? 'selected' : ''; ?>>Desistente</option>
            </select>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- CAMPOS ENDEREÇO  -->
  <div class="card shadow-sm p-3 mt-3">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="cep">Cep: </label>
        <div class="input-group">
          <input type="text" name="cep" id="cep" class="form-control" placeholder="00000-000" maxlength="9" value="<?php echo htmlspecialchars($cep); ?>" required>

          <span class="input-group-text" id="spinner" style="display: none;">
            <div class="spinner-grow spinner-grow-sm text-danger" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </span>
        </div>
      </div>

      <div id="cep-error" class="text-danger small mt-1"></div>

      <div class="col-md-6">
        <label class="form-label" for="logradouro">Logradouro: </label>
        <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?php echo htmlspecialchars($logradouro); ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="numero">Número: </label>
        <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($numero); ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="complemento">Complemento: </label>
        <input type="text" name="complemento" id="complemento" class="form-control" value="<?php echo htmlspecialchars($complemento); ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="bairro">Bairro: </label>
        <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($bairro); ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label" for="cidade">Cidade: </label>
        <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>" required>
      </div>

      <!-- SELECT ESTADOS -->
      <div class="col-md-3">
        <label class="form-label" for="estado">Estados: </label>
        <select name="estado" id="estado" class="form-select" required>
          <option value="">Selecione</option>
          <option value="AC" <?php echo ($estado === 'AC') ? 'selected' : ''; ?>>Acre (AC)</option>
          <option value="AL" <?php echo ($estado === 'AL') ? 'selected' : ''; ?>>Alagoas (AL)</option>
          <option value="AP" <?php echo ($estado === 'AP') ? 'selected' : ''; ?>>Amapá (AP)</option>
          <option value="AM" <?php echo ($estado === 'AM') ? 'selected' : ''; ?>>Amazonas (AM)</option>
          <option value="BA" <?php echo ($estado === 'BA') ? 'selected' : ''; ?>>Bahia (BA)</option>
          <option value="CE" <?php echo ($estado === 'CE') ? 'selected' : ''; ?>>Ceará (CE)</option>
          <option value="DF" <?php echo ($estado === 'DF') ? 'selected' : ''; ?>>Distrito Federal (DF)</option>
          <option value="ES" <?php echo ($estado === 'ES') ? 'selected' : ''; ?>>Espírito Santo (ES)</option>
          <option value="GO" <?php echo ($estado === 'GO') ? 'selected' : ''; ?>>Goiás (GO)</option>
          <option value="MA" <?php echo ($estado === 'MA') ? 'selected' : ''; ?>>Maranhão (MA)</option>
          <option value="MT" <?php echo ($estado === 'MT') ? 'selected' : ''; ?>>Mato Grosso (MT)</option>
          <option value="MS" <?php echo ($estado === 'MS') ? 'selected' : ''; ?>>Mato Grosso do Sul (MS)</option>
          <option value="MG" <?php echo ($estado === 'MG') ? 'selected' : ''; ?>>Minas Gerais (MG)</option>
          <option value="PA" <?php echo ($estado === 'PA') ? 'selected' : ''; ?>>Pará (PA)</option>
          <option value="PB" <?php echo ($estado === 'PB') ? 'selected' : ''; ?>>Paraíba (PB)</option>
          <option value="PR" <?php echo ($estado === 'PR') ? 'selected' : ''; ?>>Paraná (PR)</option>
          <option value="PE" <?php echo ($estado === 'PE') ? 'selected' : ''; ?>>Pernambuco (PE)</option>
          <option value="PI" <?php echo ($estado === 'PI') ? 'selected' : ''; ?>>Piauí (PI)</option>
          <option value="RJ" <?php echo ($estado === 'RJ') ? 'selected' : ''; ?>>Rio de Janeiro (RJ)</option>
          <option value="RN" <?php echo ($estado === 'RN') ? 'selected' : ''; ?>>Rio Grande do Norte (RN)</option>
          <option value="RS" <?php echo ($estado === 'RS') ? 'selected' : ''; ?>>Rio Grande do Sul (RS)</option>
          <option value="RO" <?php echo ($estado === 'RO') ? 'selected' : ''; ?>>Rondônia (RO)</option>
          <option value="RR" <?php echo ($estado === 'RR') ? 'selected' : ''; ?>>Roraima (RR)</option>
          <option value="SC" <?php echo ($estado === 'SC') ? 'selected' : ''; ?>>Santa Catarina (SC)</option>
          <option value="SP" <?php echo ($estado === 'SP') ? 'selected' : ''; ?>>São Paulo (SP)</option>
          <option value="SE" <?php echo ($estado === 'SE') ? 'selected' : ''; ?>>Sergipe (SE)</option>
          <option value="TO" <?php echo ($estado === 'TO') ? 'selected' : ''; ?>>Tocantins (TO)</option>
        </select>
      </div>
    </div>
    <div class="mt-3 text-end">
      <input type="reset" class="btn btn-danger" value="Limpar">
      <button class="btn btn-primary">Salvar</button>
    </div>
  </div>
</form>