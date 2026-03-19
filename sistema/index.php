<?php 
require_once("conexao.php");

//INSERIR UM USUÁRIO ADMINISTRADOR CASO NÃO EXISTA
$senha = '123';
$senha_crip = md5($senha);

$query = $pdo->query("SELECT * from usuarios where nivel = 'Administrador'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg == 0){
	$pdo->query("INSERT INTO usuarios SET nome = 'Admin', email = '$email_sistema', cpf = '000.000.000-00', senha = '$senha', senha_crip = '$senha_crip', nivel = 'Administrador', data = curDate(), ativo = 'Sim', foto = 'sem-foto.jpg'");
}


$query = $pdo->query("SELECT * from cargos");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg == 0){
	$pdo->query("INSERT INTO cargos SET nome = 'Administrador'");
}



 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<title><?php echo $nome_sistema ?></title>
	
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
	
	<!-- Fonts -->
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
	
	<!-- Font Awesome -->
	<link href="../css/font-awesome.min.css" rel="stylesheet" />
	
	<!-- Custom CSS -->
	<link rel="stylesheet" type="text/css" href="css/estilo-login.css">
	<link rel="icon" type="image/png" href="img/favicon.ico">
	
	<!-- jQuery -->
	<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
	<!-- Bootstrap JS -->
	<script src="../js/bootstrap.js"></script>
</head>
<body>

<div class="hero_area">
    <div class="hero_bg_box">
        <img src="../images/<?php echo $img_banner_index ?>" alt="">
    </div>
    
    <!-- Header section -->
    <header class="header_section">
        <div class="container">
            <nav class="navbar navbar-expand-lg custom_nav-container">
                <a class="navbar-brand" href="../index.php"><?php echo $nome_sistema ?></a>
                <div class="ml-auto">
                    <a title="Voltar para o Site" class="nav-link text-white" href="../index.php">
                        <i class="fa fa-home" aria-hidden="true"></i> Voltar ao Site
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Login section -->
    <section class="login_section">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-6 col-lg-5">
                    <div class="login_container">
                        <div class="login_form">
                            <div class="heading_container text-center mb-4">
                                 <h2 class="text-white">Acesso ao Sistema</h2>
                                 <p class="text-white-50">Faça login para acessar o painel administrativo</p>
                             </div>
                            
                            <form accept-charset="UTF-8" role="form" action="autenticar.php" method="post">
                                <div class="form-group mb-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <input class="form-control" placeholder="E-mail ou CPF" name="email" type="text" required>
                                    </div>
                                </div>
                                
                                <div class="form-group mb-4">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa fa-lock"></i>
                                            </span>
                                        </div>
                                        <input class="form-control" placeholder="Senha" name="senha" type="password" required>
                                    </div>
                                </div>
                                
                                <button class="btn btn-login btn-block mb-3" type="submit">
                                    <i class="fa fa-sign-in mr-2"></i>Entrar no Sistema
                                </button>
                            </form>
                            
                             <div class="text-center">
                                 <a class="recuperar-link" href="" data-toggle="modal" data-target="#exampleModal">
                                     <i class="fa fa-key mr-1"></i>Esqueceu sua senha?
                                 </a>

								<br>
								<a class="recuperar-link" href="" data-toggle="modal" data-target="#modalCadastroSaas" style="display: inline-block; margin-top: 8px;">
									<i class="fa fa-rocket mr-1"></i>Quero criar minha barbearia SaaS
								</a>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </section>
</div>

</body>
</html>




<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Recuperar Senha</h5>
        <button id="btn-fechar-rec" type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true" >&times;</span>
        </button>
      </div>
      <form method="post" id="form-recuperar">
      <div class="modal-body">
        
        	<input placeholder="Digite seu Email" class="form-control" type="email" name="email" id="email-recuperar" required>        	
       
       <br>
       <small><div id="mensagem-recuperar" align="center"></div></small>
      </div>
      <div class="modal-footer">      
        <button type="submit" class="btn btn-primary">Recuperar</button>
      </div>
  </form>
    </div>
  </div>
</div>


<div class="modal fade" id="modalCadastroSaas" tabindex="-1" role="dialog" aria-labelledby="modalCadastroSaasLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCadastroSaasLabel">Criar Conta SaaS</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="post" id="form-cadastro-saas">
        <div class="modal-body">
          <input class="form-control" type="text" name="empresa" id="saas-empresa" placeholder="Nome da Barbearia" required>
          <br>
          <input class="form-control" type="text" name="responsavel" id="saas-responsavel" placeholder="Seu Nome" required>
          <br>
          <input class="form-control" type="tel" name="telefone" id="saas-telefone" placeholder="Seu WhatsApp (DDD + número)" required>
          <small class="text-muted">Ex.: 11999998888</small>
          <br><br>
          <input class="form-control" type="email" name="email" id="saas-email" placeholder="Seu Email" required>
          <br>
          <input class="form-control" type="text" name="subdominio" id="saas-subdominio" placeholder="Subdominio desejado" required>
          <small class="text-muted">Ex.: minhabarbearia.superzap.fun</small>
          <br><br>
          <input class="form-control" type="password" name="senha" id="saas-senha" placeholder="Senha (minimo 6 caracteres)" required>
          <br>
          <input class="form-control" type="password" name="confirmar_senha" id="saas-confirmar-senha" placeholder="Confirmar Senha" required>
          <br>
          <small><div id="mensagem-cadastro-saas" align="center"></div></small>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Criar Minha Conta</button>
        </div>
      </form>
    </div>
  </div>
</div>



<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


 <script type="text/javascript">
	$("#form-recuperar").submit(function () {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: "recuperar-senha.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-recuperar').text('');
				$('#mensagem-recuperar').removeClass()
				if (mensagem.trim() == "Recuperado com Sucesso") {
					//$('#btn-fechar-rec').click();					
					$('#email-recuperar').val('');
					$('#mensagem-recuperar').addClass('text-success')
					$('#mensagem-recuperar').text('Sua Senha foi enviada para o Email')			

				} else {

					$('#mensagem-recuperar').addClass('text-danger')
					$('#mensagem-recuperar').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>


<script type="text/javascript">
	$("#form-cadastro-saas").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		$('#mensagem-cadastro-saas').removeClass();
		$('#mensagem-cadastro-saas').addClass('text-info');
		$('#mensagem-cadastro-saas').text('Criando sua conta... isso pode levar alguns segundos.');

		$.ajax({
			url: "saas/cadastro_cliente.php",
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			success: function (resposta) {
				var dados = resposta;

				if (typeof resposta === 'string') {
					try {
						dados = JSON.parse(resposta);
					} catch (e) {
						dados = {ok: false, mensagem: resposta};
					}
				}

				$('#mensagem-cadastro-saas').removeClass();

				if (dados.ok) {
					var mensagemHtml = 'Conta criada com sucesso!<br>';
					mensagemHtml += 'URL: <a href="' + dados.dominio + '" target="_blank">' + dados.dominio + '</a><br>';
					mensagemHtml += 'Email: ' + dados.email + '<br>';
					mensagemHtml += 'Senha: ' + dados.senha + '<br><br>';
					mensagemHtml += '<a href="' + dados.whatsapp_link + '" target="_blank" class="btn btn-success">';
					mensagemHtml += '<i class="fa fa-whatsapp"></i> Enviar dados via WhatsApp</a>';
					
					$('#mensagem-cadastro-saas').addClass('text-success');
					$('#mensagem-cadastro-saas').html(mensagemHtml);

					$('#saas-email').val(dados.email);
					$('#saas-senha').val('');
					$('#saas-confirmar-senha').val('');
					$('#saas-telefone').val('');
				} else {
					$('#mensagem-cadastro-saas').addClass('text-danger');
					$('#mensagem-cadastro-saas').text(dados.mensagem || 'Falha ao criar conta SaaS.');
				}
			},
			error: function () {
				$('#mensagem-cadastro-saas').removeClass();
				$('#mensagem-cadastro-saas').addClass('text-danger');
				$('#mensagem-cadastro-saas').text('Erro de comunicacao ao criar conta SaaS.');
			}
		});
	});
</script>



