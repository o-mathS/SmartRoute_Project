<?php
include 'conexao.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "DELETE FROM entregas WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Redireciona de volta para a página de entregas com sucesso
            header("Location: ../views/entregas.php?success=Entrega removida com sucesso.");
        } else {
            // Redireciona com erro
            header("Location: ../views/entregas.php?error=Erro ao remover entrega.");
        }
        $stmt->close();
    } else {
        header("Location: ../views/entregas.php?error=Erro na preparação da consulta.");
    }
} else {
    header("Location: ../views/entregas.php?error=ID inválido.");
}
?>
