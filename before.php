<?php

    /*
        # DADOS QUE VIRÃO DO BANCO DE DADOS (que devem ser colocados no html):
            -> filename_expr
            -> path_expr
        
        # DADOS QUE SERÃO INFORMADOS PELO USUÁRIO:
            -> arquivo_nm
            -> arquivo_dt

    */

    // Inicia uma nova sessão ou resume uma sessão existente
    session_start();
    
    // Obtém o nome base do arquivo a partir do valor POST 'arquivo_nm'
    $filename_orignal  = basename($_POST['arquivo_nm']); 

    // Obtém o valor POST 'filename_expr' e 'path_expr'
    $filename_expr  = $_POST['filename_expr'];
    $path_expr  = $_POST['path_expr'];

    // Armazena a data do arquivo (primeiros 10 caracteres) na variável de sessão 'v_arquivo_dt_str_amd'
    $_SESSION['v_arquivo_dt_str_amd'] = substr($_POST['arquivo_dt'], 0, 10);
    
    // Atualiza a variável de sessão 'filename' com o nome base do arquivo
    $_SESSION['filename'] = $filename_orignal;
    
    $_SESSION['file_blob'] = $_POST['file_blob'];
    /**
     * path_expr e path_expr são expressões(frases) compostas por "variáveis",
     * que irão compor o caminho de onde o arquivo pdf vai ser guardado(path_expr)
     * e o nome modificado do arquivo que será guardado (filename_expr)
     * Dessa forma, o caminho e novo nome do arquivo fica configurável de acordo com oque você quiser
     */
     //Substitui as variáveis no path_expr e filename_expr com os valores correspondentes da sessão
    foreach ($_SESSION as $variavel => $valor) {
        $path_expr = str_replace(":$variavel", $valor, $path_expr);
        $filename_expr = str_replace(":$variavel", $valor, $filename_expr);
    }
    
    // Define o caminho do diretório de destino
    $dir_destination_path = $path_expr;
    
    // Obtém a extensão do arquivo
    $info = pathinfo($filename_orignal);
    $extension = $info['extension'];
    
    // Remove caracteres especiais do filename_expr e a extensão para não ficar duplicada 
    $filename_expr = preg_replace('/[^a-zA-Z0-9_ -]/s', '', str_replace(".pdf", "", $filename_expr));

    // Define o caminho do arquivo de destino
    $file_destination_path  =  $filename_expr . "." . $extension;

    // Armazena o nome e o caminho do arquivo de destino nas variáveis de sessão
    $_SESSION['file_destination_name'] = $file_destination_path;
    $_SESSION['dir_destination_path'] = $dir_destination_path;

?>


