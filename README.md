# Assinatura_digital_PDF
Neste repositório contém um conjunto de códigos PHP que geram um PDF assinado com QRcode e outras informações 




**Para a Assinatura dos Arquivos:**
1. Usuário faz upload do arquivo.
2. Registro do arquivo é salvo no banco de dados.
3. Arquivo é guardado na pasta adequada.
4. Sistema gera uma chave hash sobre o arquivo.
5. Sistema salva no arquivo o hash utilizando atributos estendidos.
6. Sistema gera uma nova chave hash para conferir se bate com a primeira.

**Para Salvar o Arquivo:**
A. Usuário solicita o download do arquivo.
B. Sistema salva em uma tabela (`tb_arquivo_exportado`) as informações do arquivo temporário, com chave estrangeira para as tabelas `tb_arquivo` e `tb_usuario`.
C. Os passos 4, 5 e 6 são realizados sobre o arquivo temporário, mantendo os atributos estendidos do arquivo original e salvando os novos atributos com um prefixo "exportacao_".
D. Sistema devolve o arquivo temporário ao usuário.

**Para Validar o Arquivo no Futuro:**
1. Usuário faz upload do arquivo.
2. Sistema gera o hash do arquivo.
3. Sistema compara o hash gerado com o hash salvo nos atributos estendidos cujo nome começa com "exportacao_".
4. Se os hashes baterem, o sistema retorna uma mensagem indicando que a assinatura é VÁLIDA e informa os dados de quem e quando assinou.
5. Se os hashes não baterem, o sistema informa que a assinatura é INVÁLIDA ou o arquivo não está assinado.

Certifique-se de implementar os detalhes técnicos corretamente, como a geração e verificação de hashes, a manipulação adequada dos atributos estendidos e a gestão adequada das chaves estrangeiras no banco de dados.
