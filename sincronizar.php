<?php
	header('Access-Control-Allow-Origin: *');
	//header("content-type: text/javascript");
	header("Content-Type: application/json; charset=utf8");
	
	$connection = mysqli_connect("carteiraestuda.mysql.dbaas.com.br","carteiraestuda","imb240997","carteiraestuda");
	//$db = mysqli_select_db("carteiraestuda",$connection);
	
	if (isset($_POST['acao'])) {
		$acao = $_POST['acao'];
		$dados = $_POST;
		$callback = '';
	} else if (isset($_GET['acao'])) {
		$acao = $_GET['acao'];
		$dados = $_GET['dados'];
		$callback = $_GET['callback'];
	} else {
		$acao = '';
		$dados = '';
		$callback = '';
	}
	
	if ($acao == 'sincronizar') {
		$resultado = sincronizar($dados, $connection);
	}
	
	if ($acao == 'pesquisar_matricula') {
		$resultado = pesquisar_matricula($dados, $connection);
	}
	
	if ($acao == 'imagem') {
		carregar_imagem($_GET['carteira_id'], $connection);
	}
	
	if ($callback != '') {
		echo $_GET["callback"]."(".json_encode($resultado).");";
	} else {
		echo json_encode($resultado);
	}
	
	exit;
	
	function sincronizar($dados, $connection) {
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		$resultado = array();
		$resultado['status'] = '';
		$resultado['mensagem'] = '';
		$resultado['dados'] = $dados;
		$s1 = "SELECT * FROM pessoas WHERE id_pessoa = '".$dados['id_pessoa']."' ORDER BY id_pessoa";
		$q1 = mysqli_query($connection, $s1);
		$pessoas = array();
		$a = 0;
		while ($r1 = mysqli_fetch_assoc($q1)) {
			$pessoas[$a] = $r1;
			$s2 = "SELECT * FROM contas WHERE id_pessoa = '".$r1['id_pessoa']."' ORDER BY id_conta";
			$q2 = mysqli_query($connection, $s2);
			$pessoas[$a]["contas"] = array();
			$b = 0;
			while ($r2 = mysqli_fetch_assoc($q2)) {
				$pessoas[$a]["contas"][$b] = $r2;
				$s3 = "SELECT * FROM lancamentos WHERE id_conta = '".$r2['id_conta']."' ORDER BY data, id_lancamento";
				$q3 = mysqli_query($connection, $s3);
				$pessoas[$a]["contas"][$b]["lancamentos"] = array();
				$c = 0;
				while($r3 = mysqli_fetch_assoc($q3)) {
					$pessoas[$a]["contas"][$b]["lancamentos"][$c] = $r3;
					$c++;
				}
				$b++;
			}
			$a++;
		}
		$resultado['pessoas'] = $pessoas;
		return $resultado;
	}
	
	function pesquisar_matricula($dados, $connection) {
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		$resultado = array();
		$resultado['status'] = '';
		$resultado['mensagem'] = '';
		$resultado['dados'] = $dados;
		
		$carteira = array();
		
		if ($dados['matricula'] != '') {
			$s1 = "	SELECT *
					FROM carteira AS ca
					WHERE ca.numero = '" . $dados['matricula']. "'
					LIMIT 1";
			$q1 = mysqli_query($connection, $s1);
			if (mysqli_num_rows($q1) > 0) {
				$r1 = mysqli_fetch_assoc($q1);
				$carteira[0] = $r1;
				$carteira[0]["imagem"] = '';
				if (empty($r1['ativa'])) {
					$resultado['status'] = 'er';
					$resultado['mensagem'] = 'Carteira inválida';					
				} else {
					$resultado['status'] = 'ok';
					$resultado['mensagem'] = 'Carteira válida';
				}
			} else {
				$resultado['status'] = 'er';
				$resultado['mensagem'] = 'Carteira não cadastrada';
			}
		} else {
				$resultado['status'] = 'er';
				$resultado['mensagem'] = 'Por favor informe o número da carteira!';
		}
		$resultado['carteira'] = $carteira;
		return $resultado;
	}
	
	function carregar_imagem($id, $connection) {
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		if ($id != '') {
			$s1 = "	SELECT *
					FROM carteira AS ca
					WHERE id = '" . $id . "'
					LIMIT 1";
			$q1 = mysqli_query($connection, $s1);
			if (mysqli_num_rows($q1) > 0) {
				$r1 = mysqli_fetch_assoc($q1);
				$imagem = $r1['imagem'];
			} else {
				$imagem = '';
			}
		}
		echo $imagem;
		exit;
	}
	
	function sql($tabela, $dados) {
		$resultado = array();
		mysqli_query($connection, 'SET CHARACTER SET utf8');
		if(is_array($dados)) {
			$s = "SELECT * FROM `".$tabela."` WHERE id = '".$dados['id']."' AND re_id='".$dados['re_id']."'";
			$q = mysqli_query($connection, $s);
			$n = mysqli_num_rows($q);
			if ($n <= 0) {
				$campos = implode(", ", array_keys($dados));
				$valores = array_map('mysql_real_escape_string', array_values($dados));
				$valores = "'" . implode("', '", $valores) . "'";
				$sql = "INSERT INTO `".$tabela."` ($campos) VALUES ($valores)";
    			mysqli_query($connection, $sql);
			} else {
				foreach ($dados as $key => $value) {
					$value = mysql_real_escape_string($value); // this is dedicated to @Jon
					$value = "'$value'";
					$updates[] = "$key = $value";
				}
				$implodeArray = implode(', ', $updates);
				$sql = "UPDATE `".$tabela."` SET $implodeArray WHERE id='".$dados['id']."'";
				mysqli_query($connection, $sql);
			}
			if (!mysqli_error()) {
				$resultado['status'] = 'ok';
				$resultado['mensagem'] = 'Registros gravados com sucesso';
				$resultado['registro'] = $dados;
			} else {
				$resultado['status'] = 'er';
				$resultado['mensagem'] = 'Problema na gravação dos registros: ' . mysqli_error() . ', ' . $sql;
				$resultado['registro'] = $dados;
			}
		} else {
			$resultado['status'] = 'er';
			$resultado['mensagem'] = 'Dados inválidos';
			$resultado['registro'] = $dados;
		}
		return $resultado;
	}
	
	function upload_imagem($dados) {
		$arquivo	= $_FILES["arquivo"]["tmp_name"]; 
		$tamanho	= $_FILES["arquivo"]["size"];
		$tipo		= $_FILES["arquivo"]["type"];
		$nome		= $_FILES["arquivo"]["name"];

		$fp = fopen($arquivo, "rb");
		$conteudo = fread($fp, $tamanho);
		$conteudo = addslashes($conteudo);
		fclose($fp);
		$s = "UPDATE ".$dados['tb']." SET ".$dados['cp']." = '".$conteudo."' WHERE id = ".$dados['id'];
		$q = mysqli_query($connection, $s);
		
		//$resultado['status'] = 'ok';
		//$resultado['mensagem'] = $s;
		//$resultado['registro'] = $dados;
		return $s;
	}
	
	exit;
	
    function formata_data($var_data) {
         $var_dia = substr($var_data, 8, 2);
         $var_mes = substr($var_data, 5, 2);
         $var_ano = substr($var_data, 0, 4);
         $data_formatada = "$var_dia";
         $data_formatada.= "/"; 
         $data_formatada.= "$var_mes";
         $data_formatada.= "/";
         $data_formatada.= "$var_ano";
         return $data_formatada;
    }
		
    function formata_data_bd($var_data) {
         $var_dia = substr($var_data, 0, 2);
         $var_mes = substr($var_data, 3, 2);
         $var_ano = substr($var_data, 6, 4);
         $data_formatada = "$var_ano";
         $data_formatada.= "-"; 
         $data_formatada.= "$var_mes";
         $data_formatada.= "-";
         $data_formatada.= "$var_dia";
         return $data_formatada;
    }
?>