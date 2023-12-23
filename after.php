<?php 
   
    /*
        # Você só chegará aqui após ter executado o before.php !

        Os dados que serão usados aqui são aqueles recebidos inicialmente 
        do usuário e do banco, que agora estão presentes no ARRAY  $_SESSION

    */
   
   // Inicia uma nova sessão ou resume uma sessão existente
    session_start();

    // Obtém o blob do arquivo a partir do valor POST 'file_blob'
    $blob = $_SESSION['file_blob'];

    // Obtém o nome base do arquivo a partir da variável de sessão 'filename'
    $filename = basename($_SESSION['filename']); 

    // Obtém o caminho do diretório de destino a partir da variável de sessão 'dir_destination_path'
    $dir_destination_path =  $_SESSION['dir_destination_path'];

    // Cria um novo diretório no caminho especificado com permissões 0777
    my_files_create_folder($dir_destination_path, 0777);

    // Decodifica o blob do arquivo, removendo o prefixo do tipo de mídia
    $data = base64_decode(preg_replace('#^data:application/\w+;base64,#i', '', $blob));

    // Define o caminho completo do arquivo PDF
    $pdf_path = $dir_destination_path. '/' . $filename;

    // Escreve os dados decodificados no arquivo PDF
    file_put_contents($pdf_path, $data);
    
    // Gera uma chave hash SHA-256 do arquivo e armazena na variável de sessão 'hash_original'
    $_SESSION['hash_original'] = hash_file('sha256', $pdf_path);

    // Define o caminho do arquivo SQL para inserir o hash na tabela 'tb_arquivo'
    $sql_insert_arquivo ='caminho da sua sql';

    // Executa o arquivo SQL com as variáveis fornecidas
    crud_sql_execute($sql_insert_arquivo, $variaveis);
    
    // Define os metadados do arquivo para exportação
    $metadados_arquivo_expotacao = array(
        "sign_hash"     => $_SESSION['hash_original'],
        "sign_date"     => $_SESSION['v_arquivo_dt_str_amd']
    );
    
    // Para cada metadado, define um atributo estendido no arquivo
    foreach ($metadados_arquivo_expotacao as $chave => $valor){
        
        $filenamePath = $pdf_path;
        $attributeName = $chave;
        $attributeValue = $valor;
    
        $filenameEscaped = escapeshellarg($filenamePath);
        $attributeNameEscaped = escapeshellarg($attributeName);
        $attributeValueEscaped = escapeshellarg($attributeValue);
    
        // Define o comando para definir o atributo estendido
        $commandSet = "setfattr -n user.{$attributeNameEscaped} -v {$attributeValueEscaped} {$filenameEscaped}";
        $outputSet = '';
        $returnCodeSet = 0;

        // Executa o comando
        exec($commandSet, $outputSet, $returnCodeSet);
    }




    // DECLARAÇÃO DE FUNÇÕES:

    function my_files_create_folder($dir_destination_path, $permissions) {
        // Verifica se o diretório já existe
        if (!file_exists($dir_destination_path)) {
            // Tenta criar o diretório com as permissões especificadas
            if (mkdir($dir_destination_path, $permissions, true)) {
                echo "Diretório '$dir_destination_path' criado com sucesso.";
            } else {
                echo "Falha ao criar o diretório '$dir_destination_path'.";
            }
        } else {
            echo "O diretório '$dir_destination_path' já existe.";
        }
    }
    
    function crud_sql_execute($sql_file_path, $variables) {
        // Carrega o arquivo SQL
        $sql = file_get_contents($sql_file_path);
    
        // Substitui as variáveis no SQL
        foreach ($variables as $key => $value) {
            $sql = str_replace(':' . $key, $value, $sql);
        }
    
        try {
            // Cria uma nova conexão PDO
            // Substitua dbname, host, user e password pelos seus próprios valores
            $pdo = new PDO('mysql:dbname=your_database;host=your_host', 'user', 'password');
    
            // Executa o SQL
            $pdo->exec($sql);
    
            echo "SQL executado com sucesso.";
        } catch (PDOException $e) {
            echo "Erro ao executar SQL: " . $e->getMessage();
        }
    }
    


?>




<!--

    //para testar apenas: 
    //comando para pegar os atributos estendidos 
    foreach ($metadados_arquivo_expotacao as $chave => $valor){
        $filenamePath = $pdf_path;
        $attributeName = $chave;
        $attributeValue = $valor;
    
        $filenameEscaped = escapeshellarg($filenamePath);
        $attributeNameEscaped = escapeshellarg($attributeName);
        $attributeValueEscaped = escapeshellarg($attributeValue);

        $commandGet = "getfattr -n user.{$attributeNameEscaped} -e base64 --only-values --absolute-names {$filenameEscaped}";
        $outputGet = '';
        $returnCodeGet = 0;
        exec($commandGet, $outputGet, $returnCodeGet);

        $decodedValue = $outputGet[0];
        echo "Valor do atributo extendido: $decodedValue\n";
    }

-->
