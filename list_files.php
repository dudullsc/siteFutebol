<?php
header('Content-Type: application/json'); // Informa que a resposta é JSON

$galleryRelativeDir = 'images/gallery/'; // Caminho relativo a partir deste script
$galleryAbsoluteDir = __DIR__ . '/' . $galleryRelativeDir;

$imageFiles = [];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

try {
    // Usa glob para encontrar arquivos de imagem diretamente
    $files = glob($galleryAbsoluteDir . '*.{'.implode(',', $allowedExtensions).'}', GLOB_BRACE | GLOB_NOSORT);

    if ($files === false) {
        // Erro ao ler diretório (pode ser permissão na pasta pai)
         throw new Exception("Não foi possível ler o diretório da galeria.");
    }

     // Filtra apenas arquivos (ignora diretórios, se houver) e pega tempo de modificação
     $fileDetails = [];
     foreach ($files as $file) {
         if (is_file($file)) {
             $fileDetails[] = [
                 'path' => $galleryRelativeDir . basename($file), // Caminho relativo para usar no HTML src
                 'time' => filemtime($file) // Tempo da última modificação
             ];
         }
     }

    // Ordena pela data de modificação, MAIS NOVOS PRIMEIRO
    usort($fileDetails, function($a, $b) {
        return $b['time'] - $a['time']; // Descendente (b - a)
    });

    // Extrai apenas os caminhos ordenados
    $imageFiles = array_column($fileDetails, 'path');

} catch (Exception $e) {
     // Em caso de erro, retorna um JSON de erro (mas ainda status 200 OK)
     echo json_encode(['error' => $e->getMessage(), 'files' => []]);
     exit;
}

// Retorna a lista de caminhos relativos como JSON
echo json_encode(['error' => null, 'files' => $imageFiles]);
exit;

?>
