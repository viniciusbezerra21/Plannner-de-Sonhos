<?php
// Arquivo para debugar a estrutura do banco de dados
$hostname = "127.0.0.1";
$user = "root";
$password = "root";
$database = "casamento";

$conn = new mysqli($hostname, $user, $password, $database);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

echo "<h2>Estrutura da tabela 'usuario':</h2>";

$sql = "DESCRIBE usuario";
$result = $conn->query($sql);

if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Erro ao consultar estrutura: " . $conn->error;
}

echo "<h2>Exemplo de dados:</h2>";
$sql2 = "SELECT * FROM usuario LIMIT 1";
$result2 = $conn->query($sql2);
if ($result2 && $result2->num_rows > 0) {
    $row = $result2->fetch_assoc();
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    foreach ($row as $campo => $valor) {
        echo "<strong>$campo:</strong> " . (empty($valor) ? '(vazio)' : $valor) . "\n";
    }
    echo "</pre>";
} else {
    echo "Nenhum dado encontrado ou erro: " . $conn->error;
}

$conn->close();
?>