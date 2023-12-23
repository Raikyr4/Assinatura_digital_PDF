<?php

	// Carrega a biblioteca FPDI para manipulação de PDFs
	require_once '/onde/esta_sua_biblioteca/tcpdf/vendor/setasign/fpdi/src/autoload.php'; 
	// Carrega a biblioteca Endroid QR Code para geração de QR codes
	require_once '/onde/esta_sua_biblioteca/endroid_qr-code/vendor/autoload.php';


	// Código do arquivo original e hash de assinatura fornecidos via GET (que irá pegar as variáveis do link que chamou esse arquivo)
	$codigo_do_arquivo_orignal = $_GET['codigo_do_arquivo_orignal'];
	$sign_hash = $_GET['sign_hash'];

	// Busca os dados do arquivo no banco de dados
	$aDadosArquivo = buscarDadosArquivo($_GET);

	// Verifica se os dados do arquivo são válidos
	if($aDadosArquivo == null) {
		echo "PDF inválido ou usuário não autenticado! $codigo_verificacao";
		exit;
	}

	// Define o caminho completo do arquivo original
	$path = $aDadosArquivo['path'];
	$arquivo_nm = str_replace(".pdf", "", $aDadosArquivo['arquivo_nm_original']). '_exportacao.pdf';

	// Gera o caminho completo do arquivo temporário assinado
	$path_complete = GeraArquivoTemporarioAssinado($path . '/' .$aDadosArquivo['arquivo_nm_original'] , $aDadosArquivo);

	// Verifica se o arquivo temporário foi criado com sucesso
	if (file_exists($path_complete)) {
		// Configura os cabeçalhos para a exibição do PDF
		header("Content-Type: application/pdf");
		header('Content-Description: Visualização de Arquivo');
		header("Content-Transfer-Encoding: Binary"); 
		header('Content-Disposition: inline; filename="'.$arquivo_nm.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($path_complete));
		header('Accept-Ranges: bytes');
		// Lê e exibe o conteúdo do arquivo temporário
		readfile($path_complete);
	} else {
		// Exibe mensagem de link inválido
		echo "link não válido (" . $aDadosArquivo->$codigo_verificacao  . "): $path_complete";
	}



   /////////////////////////////// DECLARAÇÃO DE FUNÇÕES //////////////////////////////////////////////////////

	// Função para buscar os dados do arquivo no banco de dados
	function buscarDadosArquivo($_variaveis){
		// Caminho do arquivo SQL que contém a consulta para buscar os dados do arquivo
		$sql_path = "/get_arquivo.sql";
		// Executa a consulta SQL e retorna o resultado
		$sql = sql_read_from_file($sql_path, $_variaveis);
		$aLinhasRetorno = sql_load_in_db($sql);
		// Retorna a primeira linha do resultado
		if (count($aLinhasRetorno) > 0) {
			$aLinhaPrimeira = $aLinhasRetorno[0];
		}
		return $aLinhaPrimeira;
	}


		
	// Função para gerar um arquivo temporário assinado
	function GeraArquivoTemporarioAssinado($existing_pdf_path, $_oVariaveis) {

		// Cria uma nova instância do TCPDF para adicionar assinatura ao PDF existente
		$pdf = new \setasign\Fpdi\TcpdfFpdi(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// Define a fonte padrão para o PDF
		$pdf->SetFont('helvetica', '', 12);

		// Criação do texto a ser adicionado ao PDF
		$text = 'Proprietário(a) do Trabalho: ' . $_oVariaveis['pessoa_nm'];

		// Gera o QR code com base na URL e na hash de assinatura
		$url = "sua url que deseja usar para gerar o QRcode" . $_oVariaveis['sign_hash'];
		$qrCode = new \Endroid\QrCode\QrCode($url);
		$qrCode->setSize(300);

		// Salva o QR code como uma imagem temporária
		$qrCodePath = tempnam(sys_get_temp_dir(), 'qrcode') . '.png';
		file_put_contents($qrCodePath, $qrCode->writeString());

		// Importa cada página do PDF existente
		$pagecount = $pdf->setSourceFile($existing_pdf_path);
		for ($i = 1; $i <= $pagecount; $i++) {
			$tplidx = $pdf->importPage($i);
			$pdf->addPage();
			$pdf->useTemplate($tplidx);

			// Adiciona o texto verticalmente à margem direita
			$pdf->StartTransform();
			$pdf->Rotate(90, $pdf->getPageWidth() - 10, $pdf->getPageHeight() - 120);
			$pdf->Text($pdf->getPageWidth() - 10, $pdf->getPageHeight() - 120, $text);
			$pdf->StopTransform();

			// Adiciona o QR code na última página
			if($i == $pagecount){
				$pdf->addPage();
				$pdf->Image($qrCodePath, '', '', 50, 50);

				// Define a posição do texto
				$x = 60;
				$y = 10;
			
				$pdf->SetXY($x, $y);
				$pdf->MultiCell(100, 10, $text ."\n" . 'A chave do seu arquivo é: '. $_oVariaveis['sign_hash'] ."\n" . 'O código de verificação do seu arquivo é: ' . $_oVariaveis['codigo_verificacao'], 0, 'L', false);
			}
		}

		// Define o caminho do arquivo temporário assinado
		$pdf_path_temporario_assinado = str_replace(".pdf", "", $existing_pdf_path);
		$pdf_path_temporario_assinado = $pdf_path_temporario_assinado . '_exportacao.pdf';
		
		// Salva o PDF temporário assinado
		$pdf->Output($pdf_path_temporario_assinado, 'F');

		// Calcula e salva um hash único com base na hash da assinatura e no conteúdo do arquivo
		$exportacao_hash_sign = $_oVariaveis['sign_hash'] . hash_file('sha256', $pdf_path_temporario_assinado) . $_oVariaveis['sign_hash'];
		$exportacao_hash_sign = hash('sha256', $exportacao_hash_sign);

		/*
			+ Guardar no arquivo original:
				- sign_id: id do arquivo original na tb_arquivo;
				- : hash do arquivo original;
				- sign_date: data da assinatura do arquivo original;
			+ Guardar no arquivo exportado os seguintes atributos:
				- original_sign_hash: hash do arquivo original;
				- original_sign_date: data da assinatura do arquivo original;
				- original_owner_name: nome do proprietário do arquivo original;
				- original_document_date: data do arquivo original;
				- exportacao_sign_hash: hash do arquivo exportado (tb_arquivo_exportacao.hash_sign);
				- exportacao_file_name: nome do arquivo exportado;
				- exportacao_user_name: nome do usuário que gerou o arquivo original
		*/	
		$metadados_arquivo_expotacao = array(
			"original_sign_hash"     => $_oVariaveis['sign_hash'],
			"original_sign_date"     => $_oVariaveis['arquivo_dt'],
			"original_document_date" => $_oVariaveis['created_at'],
			"original_owner_name"    => $_oVariaveis['pessoa_nm'],
			"exportacao_sign_hash"   => $exportacao_hash_sign,
			"exportacao_file_name"   => str_replace(".pdf", "", $_oVariaveis['arquivo_nm_original']). '_exportacao.pdf',
			"exportacao_user_name"   => $_oVariaveis['created_user_name'],
		);
		
		// Salva os metadados no arquivo utilizando atributos estendidos
		foreach($metadados_arquivo_expotacao as $chave => $valor) {
			$filenamePath = $pdf_path_temporario_assinado;
			$attributeName  = $chave;
			$attributeValue = $valor;

			$filenameEscaped       = escapeshellarg($filenamePath);
			$attributeNameEscaped  = escapeshellarg($attributeName);
			$attributeValueEscaped = escapeshellarg($attributeValue);

			$commandSet = "setfattr -n user.{$attributeNameEscaped} -v {$attributeValueEscaped} {$filenameEscaped}";
			$outputSet = '';
			$returnCodeSet = 0;
			exec($commandSet, $outputSet, $returnCodeSet);
		}

		// Retorna o caminho do arquivo temporário assinado
		return $pdf_path_temporario_assinado; 
	}

 ?>
