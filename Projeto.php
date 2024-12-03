<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro para concorrer ao sorteio</title>
    <link rel="stylesheet" type="text/css" href="style.css"/> 

    <script>
        // Função para cancelar ação e limpar formulário
        function cancelarAcao() {
            document.querySelector('input[name="id"]').value = "";
            document.querySelector('input[name="nome"]').value = "";
            document.querySelector('input[name="email"]').value = "";
            document.querySelector('input[name="telefone"]').value = "";
        }
        </script>
    </head>

    <body>
<?php
// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projeto";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para limpar entradas
function limparEntrada($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Inicializar variáveis
$id = $Nome = $Email = $Telefone = "";

// Consultar o número de números disponíveis
$sqlContagem = "SELECT COUNT(*) AS total_disponiveis FROM numeros WHERE id_pertencente IS NULL";
$resultContagem = $conn->query($sqlContagem);
$totalDisponiveis = 0;

if ($resultContagem->num_rows > 0) {
    $row = $resultContagem->fetch_assoc();
    $totalDisponiveis = $row['total_disponiveis'];
}

// Adicionar concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["adicionar"])) {
    $Nome = limparEntrada($_POST["nome"]);
    $Email = limparEntrada($_POST["email"]);
    $Telefone = limparEntrada($_POST["telefone"]);

    $sqlNumero = "SELECT numero FROM numeros WHERE id_pertencente IS NULL";
    $resultNumero = $conn->query($sqlNumero);

    if ($resultNumero->num_rows > 0) {
        $numerosSemDonos = $resultNumero->fetch_all(MYSQLI_ASSOC);
        $numeroEscolhido = $numerosSemDonos[array_rand($numerosSemDonos)]['numero'];

        $sql = "INSERT INTO concorrentes (nome, celular, email) VALUES ('$Nome', '$Telefone', '$Email')";

        if ($conn->query($sql) === TRUE) {
            $idConcorrente = $conn->insert_id;
            $sqlAtualizarNumero = "UPDATE numeros SET id_pertencente = $idConcorrente WHERE numero = $numeroEscolhido";
            $conn->query($sqlAtualizarNumero);

            echo "<p>Participante cadastrado com sucesso! Número $numeroEscolhido vinculado.</p>";
        } else {
            echo "<p>Erro ao cadastrar participante: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Não há números disponíveis para vincular.</p>";
    }
}

// Editar concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar"])) {
    $id = limparEntrada($_POST["id"]);
    $sql = "SELECT * FROM concorrentes WHERE id=$id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $Nome = $row["nome"];
        $Email = $row["email"];
        $Telefone = $row["celular"];
    }
}

// Atualizar concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["atualizar"])) {
    $id = limparEntrada($_POST["id"]);
    $Nome = limparEntrada($_POST["nome"]);
    $Email = limparEntrada($_POST["email"]);
    $Telefone = limparEntrada($_POST["telefone"]);

    $sql = "UPDATE concorrentes SET nome='$Nome', celular='$Telefone', email='$Email' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Participante atualizado com sucesso!</p>";
        $id = $Nome = $Email = $Telefone = "";
    } else {
        echo "<p>Erro ao atualizar participante: " . $conn->error . "</p>";
    }
}

// Gerar novo número
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["gerar_novo_numero"])) {
    $id = limparEntrada($_POST["id"]);
    $sqlNumeroAtual = "SELECT n.numero FROM numeros n JOIN concorrentes c ON n.id_pertencente = c.id WHERE c.id = $id";
    $resultNumeroAtual = $conn->query($sqlNumeroAtual);

    if ($resultNumeroAtual->num_rows > 0) {
        $numeroAtual = $resultNumeroAtual->fetch_assoc()['numero'];
        $sqlLiberarNumero = "UPDATE numeros SET id_pertencente = NULL WHERE numero = $numeroAtual";
        $conn->query($sqlLiberarNumero);

        $sqlNumero = "SELECT numero FROM numeros WHERE id_pertencente IS NULL";
        $resultNumero = $conn->query($sqlNumero);

        if ($resultNumero->num_rows > 0) {
            $numerosSemDonos = $resultNumero->fetch_all(MYSQLI_ASSOC);
            $novoNumeroEscolhido = $numerosSemDonos[array_rand($numerosSemDonos)]['numero'];
            $sqlAtualizarNumero = "UPDATE numeros SET id_pertencente = $id WHERE numero = $novoNumeroEscolhido";
            $conn->query($sqlAtualizarNumero);

            echo "<p>Número atualizado com sucesso! Novo número vinculado: $novoNumeroEscolhido.</p>";
        } else {
            echo "<p>Não há números disponíveis para vincular.</p>";
        }
    }
}

// Excluir concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir"])) {
    $id = limparEntrada($_POST["id"]);
    $sqlNumero = "SELECT n.numero FROM numeros n JOIN concorrentes c ON n.id_pertencente = c.id WHERE c.id = $id";
    $resultNumero = $conn->query($sqlNumero);

    if ($resultNumero->num_rows > 0) {
        $numeroVinculado = $resultNumero->fetch_assoc()['numero'];
        $sqlLiberarNumero = "UPDATE numeros SET id_pertencente = NULL WHERE numero = $numeroVinculado";
        $conn->query($sqlLiberarNumero);
    }

    $sql = "DELETE FROM concorrentes WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Participante excluído com sucesso!</p>";
    } else {
        echo "<p>Erro ao excluir participante: " . $conn->error . "</p>";
    }
}
?>

<h1>Cadastro para concorrer ao sorteio</h1>
<h2>Números disponíveis para sorteio: <?php echo $totalDisponiveis; ?></h2>

<!-- Formulário -->
<form method="POST" action="">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div style="display: flex; gap: 10px; align-items: center;">
        <input type="text" name="nome" placeholder="Nome" value="<?php echo $Nome; ?>" required>
        <input type="email" name="email" placeholder="Email" value="<?php echo $Email; ?>" required>
        <input type="text" name="telefone" placeholder="Telefone" value="<?php echo $Telefone; ?>" required>
        <?php if ($id): ?>
            <button type="submit" name="atualizar">Atualizar</button>
            <button type="submit" name="gerar_novo_numero">Gerar Novo Número</button>
        <?php else: ?>
            <button type="button" onclick="cancelarAcao()">Cancelar</button>
            <button type="submit" name="adicionar">Adicionar</button>
        <?php endif; ?>
    </div>
</form>

<!-- Lista de Participantes -->
<h2 style="color:white;">Lista de Participantes</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Telefone</th>
            <th>Número Vinculado</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT c.id, c.nome, c.email, c.celular, n.numero FROM concorrentes c LEFT JOIN numeros n ON c.id = n.id_pertencente";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["nome"] . "</td>
                        <td>" . $row["email"] . "</td>
                        <td>" . $row["celular"] . "</td>
                        <td>" . ($row["numero"] ? $row["numero"] : "Nenhum") . "</td>
                        <td>
                            <form method='POST' action='' style='display:inline;'>
                                <input type='hidden' name='id' value='" . $row["id"] . "'>
                                <button type='submit' name='editar'>Editar</button>
                            </form>
                            <form method='POST' action='' style='display:inline;'>
                                <input type='hidden' name='id' value='" . $row["id"] . "'>
                                <button type='submit' name='excluir'>Excluir</button>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Nenhum participante encontrado.</td></tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>

<?php
// Fechar a conexão
$conn->close();
?>