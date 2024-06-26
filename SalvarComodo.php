<?php
// Inclua o arquivo de conexão com o banco de dados
include 'conexao.php';

// Recuperar o último ID de local adicionado
$sqlUltimoLocalId = "SELECT MAX(id) AS ultimo_local_id FROM local";
$resultadoUltimoLocalId = $conexao->query($sqlUltimoLocalId);
$ultimoLocalId = 0;
if ($resultadoUltimoLocalId && $resultadoUltimoLocalId->num_rows > 0) {
    $row = $resultadoUltimoLocalId->fetch_assoc();
    $ultimoLocalId = $row['ultimo_local_id'];
}

// Verifique se o formulário foi submetido para adicionar cômodo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nome-comodo']) && isset($_POST['nivel-interferencia'])) {
    // Recupere os dados do formulário e valide-os
    $nomeComodo = mysqli_real_escape_string($conexao, $_POST['nome-comodo']);
    $nivelInterferencia = intval($_POST['nivel-interferencia']); // Converte para inteiro

    // Verifique se já existe um cômodo com o mesmo nome no mesmo local_id
    $sqlVerificarComodo = "SELECT id FROM comodos WHERE nome = '$nomeComodo' AND local_id = $ultimoLocalId";
    $resultadoVerificarComodo = $conexao->query($sqlVerificarComodo);
    if ($resultadoVerificarComodo && $resultadoVerificarComodo->num_rows > 0) {
        echo "Erro ao inserir cômodo: Já existe um cômodo com o mesmo nome neste local.";
    } else {
        // Construa a consulta SQL para inserir o cômodo usando prepared statements
        $stmt = $conexao->prepare("INSERT INTO comodos (local_id, nome, nivel_interferencia) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $ultimoLocalId, $nomeComodo, $nivelInterferencia);

        // Execute a consulta
        if ($stmt->execute()) {
            echo "Cômodo inserido com sucesso!";
        } else {
            // Tratamento de erro mais detalhado
            if ($conexao->errno == 1062) {
                echo "Erro ao inserir cômodo: O nome do cômodo já existe.";
            } else {
                echo "Erro ao inserir cômodo: " . $conexao->error;
            }
        }

        // Feche a instrução preparada
        $stmt->close();
    }
}

// Consulta para recuperar os cômodos associados ao último local_id adicionado
$sqlConsulta = "SELECT nome FROM comodos WHERE local_id = $ultimoLocalId";
$resultadoConsulta = $conexao->query($sqlConsulta);
$comodos = [];
if ($resultadoConsulta && $resultadoConsulta->num_rows > 0) {
    while ($row = $resultadoConsulta->fetch_assoc()) {
        $comodos[] = $row['nome'];
    }
}

// Feche a conexão com o banco de dados
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medição de Sinais de Rede - Salvar Cômodo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh; /* Altura mínima da viewport */
            display: flex;
            flex-direction: column;
        }
        header {
            background-color: #333;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        nav {
            background-color: #f4f4f4;
            padding: 10px;
            text-align: center;
        }
        .container {
            flex: 1; /* Ajusta a div container para ocupar todo o espaço disponível */
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-top: 20px;
        }
        .form-container,
        .comodos-container {
            width: 45%;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 10px;
            margin: 0 10px;
            box-sizing: border-box;
        }
        .form-container {
            border: 2px solid #333;
        }
        input[type="text"],
        input[type="number"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin: 5px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
            float: left;
        }
        input[type="submit"] {
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            clear: both;
            float: none;
            margin-top: 10px;
        }
        #comodos-inseridos h2 {
            margin-bottom: 10px;
        }
        #comodos-inseridos p {
            margin: 5px 0;
        }
        footer {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <h1>Inserir Cômodo</h1>
    </header>
    
    <nav>
        <a href="#">Inserir Cômodos</a> |
        <a href="#">Medições</a> |
        <a href="#">Mapa de Calor</a>
    </nav>

    <div class="container">
        <!-- Formulário para inserir o nome do cômodo e o nível de interferência -->
        <div class="form-container">
            <form id="inserir-comodo" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <h2>Adicionar Cômodo</h2>
                <label for="nome-comodo">Nome do Cômodo:</label>
                <input type="text" id="nome-comodo" name="nome-comodo" required><br>
                <label for="nivel-interferencia">Nível de Interferência:</label>
                <input type="number" id="nivel-interferencia" name="nivel-interferencia" required><br>
                <input type="submit" name="submit" value="Cadastrar Cômodo">
            </form>
        </div>

        <!-- Seção dos cômodos inseridos -->
        <div class="comodos-container" id="comodos-inseridos">
            <?php
            if (!empty($comodos)) {
                echo "<h2>Cômodos Inseridos:</h2>";
                foreach ($comodos as $comodo) {
                    echo "<p>$comodo 
                            <a href='#' class='editar-comodo' data-comodo='$comodo'>Editar</a> 
                            <a href='#' class='excluir-comodo' data-comodo='$comodo'>Excluir</a>
                        </p>";
                }
            }
            ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Medição de Sinais de Rede</p>
    </footer>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            // Adicionar Comodo
            $('#inserir-comodo').submit(function(event){
                event.preventDefault(); // Impede o envio do formulário padrão
                var formData = $(this).serialize(); // Serializa os dados do formulário
                $.ajax({
                    type: 'POST',
                    url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>', // Script PHP para inserir o cômodo
                    data: formData,
                    success: function(response){
                        alert(response); // Exibe a mensagem de sucesso ou erro
                        // Atualiza a lista de cômodos
                        $('#comodos-inseridos').load('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> #comodos-inseridos');
                    }
                });
            });

            // Editar Comodo
            $(document).on('click', '.editar-comodo', function(){
                var comodo = $(this).data('comodo');
                var novoNome = prompt('Novo nome do cômodo:', comodo);
                if (novoNome != null) {
                    $.ajax({
                        type: 'POST',
                        url: 'atualizar_comodo.php', // Script PHP para atualizar o cômodo
                        data: { nome_comodo_atual: comodo, novo_nome_comodo: novoNome },
                        success: function(response){
                            alert(response); // Exibe a mensagem de sucesso ou erro
                            // Atualiza a lista de cômodos
                            $('#comodos-inseridos').load('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> #comodos-inseridos');
                        }
                    });
                }
            });

            // Excluir Comodo
            $(document).on('click', '.excluir-comodo', function(){
                var comodo = $(this).data('comodo');
                if (confirm('Tem certeza que deseja excluir o cômodo "' + comodo + '"?')) {
                    $.ajax({
                        type: 'POST',
                        url: 'deletar_comodo.php', // Script PHP para excluir o cômodo
                        data: { nome_comodo: comodo },
                        success: function(response){
                            alert(response); // Exibe a mensagem de sucesso ou erro
                            // Atualiza a lista de cômodos
                            $('#comodos-inseridos').load('<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?> #comodos-inseridos');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
