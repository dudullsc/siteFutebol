<?php
// --- Configurações ---
$relativeTargetDir = "images/gallery/"; 
$absoluteTargetDir = __DIR__ . '/' . $relativeTargetDir; 
$maxFileSize = 2 * 1024 * 1024; // 2 Megabytes
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFiles = 10; // Limite máximo de arquivos na galeria
// --- Fim Configurações ---

header('Content-Type: application/json'); 
$response = ['status' => 'error', 'message' => 'Erro desconhecido.']; 

try {
    // Garante que diretório existe e tem permissão de escrita
    if (!file_exists($absoluteTargetDir)) {
        if (!mkdir($absoluteTargetDir, 0775, true)) {
             throw new Exception("Falha ao criar diretório: " . $absoluteTargetDir);
        }
    }
     if (!is_writable($absoluteTargetDir)) {
         @chmod($absoluteTargetDir, 0775); // Tenta ajustar permissão
         if (!is_writable($absoluteTargetDir)) {
             throw new Exception("Sem permissão de escrita no diretório: " . $absoluteTargetDir);
         }
     }

    // Verifica envio e erros
    if (!isset($_FILES['photoFile']) || $_FILES['photoFile']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = isset($_FILES['photoFile']['error']) ? $_FILES['photoFile']['error'] : UPLOAD_ERR_NO_FILE;
         throw new Exception("Nenhum arquivo ou erro no upload (Código: " . $errorCode . ").");
    }

    $file = $_FILES['photoFile'];
    $fileTmpPath = $file['tmp_name'];
    $fileName = basename($file['name']); 
    $fileSize = $file['size'];
    $fileType = mime_content_type($fileTmpPath); 

    // Validações
    if ($fileSize > $maxFileSize) throw new Exception("Arquivo muito grande (> " . ($maxFileSize / 1024 / 1024) . "MB).");
    if (!in_array($fileType, $allowedTypes)) throw new Exception("Tipo de arquivo inválido (Somente JPG, PNG, GIF, WEBP).");

    // Sanitiza nome
    $fileName = preg_replace("/[^A-Z0-9._-]/i", "_", $fileName);
    if (strpos($fileName, '.') === 0) $fileName = "_" . substr($fileName, 1);

    $targetFilePath = $absoluteTargetDir . $fileName;

    // --- Lógica de Limite (10 arquivos) ---
    $files = glob($absoluteTargetDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE | GLOB_NOSORT);
    if ($files === false) $files = []; 
    // Garante que $files é um array antes de contar
    if (!is_array($files)) $files = [];

    if (count($files) >= $maxFiles) {
        array_multisort(array_map('filemtime', $files), SORT_ASC, $files); 
        if (isset($files[0]) && is_writable($files[0])) { 
            if (unlink($files[0])) { 
                 error_log("Arquivo antigo removido: " . basename($files[0])); 
            } else {
                 error_log("Falha ao remover arquivo antigo: " . basename($files[0]));
            }
        } else if (isset($files[0])) {
             error_log("Arquivo mais antigo não tem permissão de escrita ou não pôde ser acessado: " . basename($files[0]));
        } else {
            error_log("Não foi possível determinar o arquivo mais antigo para remover (lista vazia após sort?).");
        }
    }
    // --- Fim da Lógica de Limite ---

    // Move o arquivo
    if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
        $response['status'] = 'success';
        $response['message'] = 'Foto "' . htmlspecialchars($fileName) . '" enviada!';
        $response['filepath'] = $relativeTargetDir . $fileName; 
    } else {
        // Log de erro mais detalhado se move_uploaded_file falhar
        $lastError = error_get_last();
        $moveErrorMsg = "Falha ao mover arquivo.";
        if ($lastError !== null) {
            $moveErrorMsg .= " Detalhes: " . $lastError['message'];
        }
         error_log($moveErrorMsg . " Origem: " . $fileTmpPath . " Destino: " . $targetFilePath);
         throw new Exception($moveErrorMsg);
    }

} catch (Exception $e) {
    http_response_code(400); 
    $response['message'] = $e->getMessage();
     error_log("Erro no Upload PHP: " . $e->getMessage());
}

// Resposta JSON
echo json_encode($response);
exit; 

?>
