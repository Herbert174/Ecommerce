<?php  

    session_start();

    include_once("PagSeguroLibrary/PagSeguroLibrary.php");
    $paymentRequest = new PagSeguroPaymentRequest();

    //$frete = 0;

    $valor = (float)$_SESSION['valor'];
    $frete = (float)$_SESSION['preco_frete'];

    $valortotal = $valor;
    $valorfrete = $frete;
    $valorcompra = $valortotal + $valorfrete;

    require_once('db.class.php');
    $id_usuario  = $_SESSION['id_usuario'];
    $usuario     = $_SESSION['usuario'];

    $cep         = $_SESSION['cep'];
	$endereco    = $_SESSION['endereco'];
	$numero      = $_SESSION['numero'];
	$complemento = $_SESSION['complemento'];
	$bairro      = $_SESSION['bairro'];
	$cidade      = $_SESSION['cidade'];
	$estado      = $_SESSION['estado'];
	$pais        = $_SESSION['pais'];
    $lista_produtos = "";

    $sql = " SELECT * FROM carrinho_de_compras WHERE id = '$id_usuario' ";

    $objDb = new database();//Instância a classe
    $link = $objDb -> conecta_mysql();//Executa função de conexão com o MySQL

    $resultado_carrinho = mysqli_query($link, $sql);

    if($resultado_carrinho)
        {
        while($produto = mysqli_fetch_array($resultado_carrinho))
            {
            $produto_id   = $produto['id_produto'];
            $produto_qntd = $produto['qntd_produto'];

            $sql = " SELECT * FROM produtos WHERE id_produto = '$produto_id' ";
            $resultado_produto = mysqli_query($link, $sql);
            if($resultado_produto)
                {
                while($dados_produto = mysqli_fetch_array($resultado_produto))
                    {
                    $nome_produto        = $dados_produto['nome_produto'];
                    $preco_produto       = $dados_produto['preco'];

                    $lista_produtos .= " Nome: $nome_produto <br> Qntd: $produto_qntd <br> Preco unitario: $preco_produto <br><br> ";
                    }
                $paymentRequest->addItem($produto_id, $nome_produto, $produto_qntd, $preco_produto);
                }else{
                     echo "Erro na consulta do banco de dados";
                     }
            }
        }

        $sedexCode = PagSeguroShippingType::getCodeByType('SEDEX');  
        $paymentRequest->setShippingType($sedexCode); 
        $paymentRequest->setShippingAddress(  
            $cep,  
            $endereco,  
            $numero,  
            $complemento,  
            $bairro,  
            $cidade,  
            $estado,  
            $pais 
          );  

    $paymentRequest->addItem('0003', 'Frete',  1, $valorfrete);

    $paymentRequest->setCurrency("BRL");

    $codigoReferencia = uniqid();

    // Referenciando a transação do PagSeguro em seu sistema  
    $paymentRequest->setReference($codigoReferencia);  
      
    // URL para onde o comprador será redirecionado (GET) após o fluxo de pagamento  
    $paymentRequest->setRedirectUrl("https://hmsystem.online/index");
      
    // URL para onde serão enviadas notificações (POST) indicando alterações no status da transação  
    $paymentRequest->addParameter('notificationURL', 'https://hmsystem.online/notificacoes_pagamentos.php');
    try {
        $credentials = PagSeguroConfig::getAccountCredentials(); // getApplicationCredentials()  
        $checkoutUrl = $paymentRequest->register($credentials);  

        $_SESSION['link_pagseguro'] = $checkoutUrl;
        
        }catch(PagSeguroServiceException $e)
            {  
            die('<p>Não foi possivel calcular o frete corretamente, por favor verifique o CEP</p>');
            }

    $sql = " INSERT INTO transacoes(codigo_referencia, id_usuario, valor_compra, nome_comprador, endereco, numero, complemento, bairro, cep, cidade, estado, pais, produtos) values('$codigoReferencia', '$id_usuario', '$valorcompra', '$usuario', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$cidade', '$estado', '$pais', '$lista_produtos') ";

    if(mysqli_query($link, $sql))//Envia o codigo ao banco de dados, cadastrando as informações do produto enviado pelo usuario
		{
		}else{
			 echo 'Erro ao registrar transação no banco de dados';
			 }

    echo "<a class='link-custom' href='#'><button class='btn btn-default btn-block botao btn-blue' onclick='envia_pagseguro();'>Avançar para o Pagseguro</button></a>";

?>