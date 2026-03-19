<?php 
require_once("cabecalho.php");
$data_atual = date('Y-m-d');
?>
<style type="text/css">
	.sub_page .hero_area {
		min-height: auto;
	}
</style>

</div>

<div class="footer_section" style="background: #5a8e94; ">
	<div class="container" >
		<div class="footer_content " >
			<!-- Progress Steps -->
			<div class="steps-container" style="margin-bottom: 30px;">
				<div class="step-progress">
					<div class="step active" id="step-1">
						<div class="step-number">1</div>
						<div class="step-title">Dados</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-2">
						<div class="step-number">2</div>
						<div class="step-title">Data</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-3">
						<div class="step-number">3</div>
						<div class="step-title">Horário</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-4">
						<div class="step-number">4</div>
						<div class="step-title">Serviço</div>
					</div>
					<div class="step-line"></div>
					<div class="step" id="step-5">
						<div class="step-number">5</div>
						<div class="step-title">Resumo</div>
					</div>
				</div>
			</div>

			<div id="step-feedback" class="step-feedback" style="display: none;"></div>

			<div class="quick-summary-card" id="quick-summary-card">
				<div class="quick-summary-header">
					<h5>Resumo rapido</h5>
					<span>Atualiza em tempo real</span>
				</div>
				<div class="quick-summary-grid">
					<div class="quick-summary-item" id="quick-item-nome">
						<small>Nome</small>
						<strong id="quick-nome">Nao informado</strong>
					</div>
					<div class="quick-summary-item" id="quick-item-telefone">
						<small>Telefone</small>
						<strong id="quick-telefone">Nao informado</strong>
					</div>
					<div class="quick-summary-item" id="quick-item-data">
						<small>Data</small>
						<strong id="quick-data">Nao selecionada</strong>
					</div>
					<div class="quick-summary-item" id="quick-item-funcionario">
						<small>Profissional</small>
						<strong id="quick-funcionario">Nao selecionado</strong>
					</div>
					<div class="quick-summary-item" id="quick-item-hora">
						<small>Horario</small>
						<strong id="quick-hora">Nao selecionado</strong>
					</div>
					<div class="quick-summary-item" id="quick-item-servico">
						<small>Servico</small>
						<strong id="quick-servico">Nao selecionado</strong>
					</div>
				</div>
			</div>

			<div class="mobile-mini-summary" id="mobile-mini-summary" aria-live="polite">
				<div class="mini-summary-main" id="mini-summary-main">Preencha os dados iniciais para comecar.</div>
				<div class="mini-summary-sub">
					<span id="mini-summary-step">Etapa 1 de 5</span>
					<span id="mini-summary-next">Aguardando dados</span>
				</div>
			</div>

			<form id="form-agenda" method="post" style="margin-top: -25px !important">
			<div class="footer_form footer-col">
				
				<!-- Step 1: Dados Pessoais -->
				<div class="step-content" id="step-content-1">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Seus Dados</h4>
					<div class="form-group">
						<input class="form-control" type="text" name="telefone" id="telefone" placeholder="Seu Telefone DDD + número" required />
					</div>
					<div class="form-group">
						<input class="form-control" type="text" name="nome" id="nome" placeholder="Seu Nome" required />
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-next" onclick="nextStep(1)">Próximo</button>
					</div>
					<div class="step-lock-hint" id="hint-step-1">Preencha nome e telefone para liberar o proximo passo.</div>
				</div>

				<!-- Step 2: Data e Profissional -->
				<div class="step-content" id="step-content-2" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Data e Profissional</h4>
					<div class="form-group">
						<input onchange="mudarFuncionario(); sincronizarDataRapida();" class="form-control" type="date" name="data" id="data" value="<?php echo $data_atual ?>" min="<?php echo $data_atual ?>" required />
						<div class="quick-date-buttons">
							<button type="button" class="quick-date-btn" onclick="selecionarDataRapida(this, 0)">Hoje</button>
							<button type="button" class="quick-date-btn" onclick="selecionarDataRapida(this, 1)">Amanhã</button>
							<button type="button" class="quick-date-btn" onclick="selecionarDataRapida(this, 7)">+7 dias</button>
						</div>
					</div>
					<div class="form-group">
						<div class="professionals-grid">
							<?php 
							$query = $pdo->query("SELECT * FROM usuarios where atendimento = 'Sim' ORDER BY id desc");
							$res = $query->fetchAll(PDO::FETCH_ASSOC);
							$total_reg = @count($res);
							if($total_reg > 0){
								for($i=0; $i < $total_reg; $i++){
									$foto = $res[$i]['foto'];
									if($foto == "" || $foto == "sem-foto.jpg"){
										$foto = "sistema/img/logo.png";
									} else {
										$foto = "sistema/painel/img/perfil/".$foto;
									}
									echo '<div class="professional-card" onclick="selecionarProfissional('.$res[$i]['id'].', \''.addslashes($res[$i]['nome']).'\')" data-id="'.$res[$i]['id'].'">';
									echo '<div class="professional-photo">';
									echo '<img src="'.$foto.'" alt="'.$res[$i]['nome'].'" onerror="this.src=\'sistema/img/logo.png\'">';
									echo '</div>';
									echo '<div class="professional-info">';
									echo '<h5>'.$res[$i]['nome'].'</h5>';
									echo '<p>'.$res[$i]['nivel'].'</p>';
									echo '</div>';
									echo '<div class="professional-check"><i class="fa fa-check"></i></div>';
									echo '</div>';
								}
							}
							?>
						</div>
						<input type="hidden" id="funcionario" name="funcionario" required>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(2)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(2)">Próximo</button>
					</div>
					<div class="step-lock-hint" id="hint-step-2">Escolha data e profissional para continuar.</div>
				</div>

				<!-- Step 3: Horário -->
				<div class="step-content" id="step-content-3" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Horário Disponível</h4>
					<div class="form-group"> 									
						<div id="listar-horarios">
							
						</div>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(3)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(3)">Próximo</button>
					</div>
					<div class="step-lock-hint" id="hint-step-3">Selecione um horario para avancar.</div>
				</div>

				<!-- Step 4: Serviço -->
				<div class="step-content" id="step-content-4" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Serviço Desejado</h4>
					<div class="form-group">
						<div class="services-grid">
							<?php 
							$query = $pdo->query("SELECT * FROM servicos ORDER BY nome asc");
							$res = $query->fetchAll(PDO::FETCH_ASSOC);
							$total_reg = @count($res);
							if($total_reg > 0){
								for($i=0; $i < $total_reg; $i++){
									$foto = $res[$i]['foto'];
									if($foto == "" || $foto == "sem-foto.jpg"){
										$foto = "sistema/img/logo.png";
									} else {
										$foto = "sistema/painel/img/servicos/".$foto;
									}
									$valor = $res[$i]['valor'];
									$valorF = number_format($valor, 2, ',', '.');
									echo '<div class="service-card" onclick="selecionarServico('.$res[$i]['id'].', \''.addslashes($res[$i]['nome']).'\')" data-id="'.$res[$i]['id'].'">';
									echo '<div class="service-photo">';
									echo '<img src="'.$foto.'" alt="'.$res[$i]['nome'].'" onerror="this.src=\'sistema/img/logo.png\'">';
									echo '</div>';
									echo '<div class="service-info">';
									echo '<h5>'.$res[$i]['nome'].'</h5>';
									echo '<p>R$ '.$valorF.'</p>';
									echo '</div>';
									echo '<div class="service-check"><i class="fa fa-check"></i></div>';
									echo '</div>';
								}
							}
							?>
						</div>
						<input type="hidden" id="servico" name="servico" required>
					</div>
					<div class="step-buttons">
						<button type="button" class="btn-prev" onclick="prevStep(4)">Anterior</button>
						<button type="button" class="btn-next" onclick="nextStep(4)">Próximo</button>
					</div>
					<div class="step-lock-hint" id="hint-step-4">Escolha um servico para ir ao resumo.</div>
				</div>

				<!-- Step 5: Confirmação -->
				<div class="step-content" id="step-content-5" style="display: none;">
					<h4 style="color: white; text-align: center; margin-bottom: 20px;">Resumo do Agendamento</h4>
					
					<div class="summary-box" style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-bottom: 25px; color: white;">
						<h5 style="margin-bottom: 15px; color: #ffffff; font-weight: bold;">📋 Resumo do Agendamento:</h5>
						<div id="summary-content"></div>
					</div>

					<div class="form-group" style="margin-bottom: 25px;"> 						
						<input maxlength="100" type="text" class="form-control" name="obs" id="obs" placeholder="💬 Observações caso exista alguma..." style="border-radius: 8px; padding: 12px;">
					</div>

					<!-- Botões para novo agendamento -->
					<div class="confirmation-buttons" id="new-appointment-buttons">
						<div class="button-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
							<button type="button" class="btn-prev-final" onclick="prevStep(5)" style="flex: 1;">← Voltar</button>
							<button onclick="salvar()" class="btn-confirm" type="submit" style="flex: 2;">
								<span id='botao_salvar'>✅ Confirmar Agendamento</span>
							</button>
						</div>
					</div>


				</div>

                <br><br>
               <!-- Mensagem de Sucesso Melhorada -->
               <div id="mensagem" class="mensagem-container" align="center"></div>			

               <input type="text" id="data_oculta" style="display: none">	
                <input type="hidden" id="id" name="id">	
                 <input type="hidden" id="hora_rec" name="hora_rec">	
                  <input type="hidden" id="nome_func" name="nome_func">	
                  <input type="hidden" id="data_rec" name="data_rec">	
                    <input type="hidden" id="nome_serv" name="nome_serv">			
				

			</div>



		</form>







		</div>


	</div>


</div>




<?php require_once("rodape.php") ?>













<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Função para criar efeito confetti
function criarConfetti() {
	// Criar container de confetti se não existir
	let confettiContainer = document.querySelector('.confetti-container');
	if (!confettiContainer) {
		confettiContainer = document.createElement('div');
		confettiContainer.className = 'confetti-container';
		document.body.appendChild(confettiContainer);
	}
	
	// Limpar confetti anterior
	const existingConfetti = confettiContainer.querySelectorAll('.confetti');
	existingConfetti.forEach(c => c.remove());
	
	// Criar novo confetti
	for (let i = 0; i < 50; i++) {
		const confetti = document.createElement('div');
		confetti.className = 'confetti';
		
		// Cores aleatórias
		const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff', '#5f27cd', '#00d2d3', '#ffd700'];
		confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
		
		// Posição e timing aleatórios
		confetti.style.left = Math.random() * 100 + '%';
		confetti.style.animationDelay = Math.random() * 2 + 's';
		confetti.style.animationDuration = (Math.random() * 2 + 3) + 's';
		
		// Tamanho aleatório
		const size = Math.random() * 8 + 6; // 6px a 14px
		confetti.style.width = size + 'px';
		confetti.style.height = size + 'px';
		
		confettiContainer.appendChild(confetti);
		
		// Remover após animação
		setTimeout(() => {
			if (confetti.parentNode) {
				confetti.parentNode.removeChild(confetti);
			}
		}, 6000);
	}
	
	// Remover container após todas as animações
	setTimeout(() => {
		if (confettiContainer.parentNode) {
			confettiContainer.parentNode.removeChild(confettiContainer);
		}
	}, 8000);
}
</script>

<style type="text/css">
	.select2-selection__rendered {
		line-height: 45px !important;
		font-size:16px !important;
		color:#000 !important;

	}

	.select2-selection {
		height: 45px !important;
		font-size:16px !important;
		color:#000 !important;
		border-radius: 25px !important;

	}

	/* Form Controls Styling */
	.form-control {
		border-radius: 25px !important;
		padding: 12px 20px !important;
		border: 2px solid rgba(255,255,255,0.3) !important;
		background: rgba(255,255,255,0.1) !important;
		color: white !important;
		font-size: 16px !important;
		transition: all 0.3s ease !important;
	}

	.form-control:focus {
		border-color: #be2623 !important;
		background: rgba(255,255,255,0.2) !important;
		box-shadow: 0 0 10px rgba(190, 38, 35, 0.3) !important;
		color: white !important;
	}

	.form-control::placeholder {
		color: rgba(255,255,255,0.7) !important;
	}

	.step-feedback {
		margin: -10px auto 20px;
		max-width: 760px;
		padding: 12px 16px;
		border-radius: 12px;
		font-size: 14px;
		font-weight: 600;
		text-align: center;
	}

	.step-feedback.error {
		background: rgba(220, 53, 69, 0.2);
		border: 1px solid rgba(220, 53, 69, 0.6);
		color: #ffd7dc;
	}

	.step-feedback.info {
		background: rgba(23, 162, 184, 0.18);
		border: 1px solid rgba(23, 162, 184, 0.55);
		color: #d4f7ff;
	}

	.quick-summary-card {
		position: sticky;
		top: 10px;
		z-index: 30;
		margin: 0 auto 24px;
		padding: 16px;
		border-radius: 16px;
		background: rgba(12, 36, 43, 0.78);
		border: 1px solid rgba(255,255,255,0.22);
		backdrop-filter: blur(8px);
		max-width: 820px;
		box-shadow: 0 8px 28px rgba(0, 0, 0, 0.2);
	}

	.quick-summary-header {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
	}

	.quick-summary-header h5 {
		margin: 0;
		font-size: 16px;
		color: #fff;
		font-weight: 700;
	}

	.quick-summary-header span {
		font-size: 12px;
		color: rgba(255,255,255,0.72);
	}

	.quick-summary-grid {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 10px;
	}

	.quick-summary-item {
		border-radius: 12px;
		padding: 10px 12px;
		background: rgba(255,255,255,0.08);
		border: 1px solid rgba(255,255,255,0.15);
		display: flex;
		flex-direction: column;
		gap: 4px;
		min-height: 62px;
		transition: all 0.25s ease;
	}

	.quick-summary-item small {
		font-size: 11px;
		text-transform: uppercase;
		letter-spacing: 0.04em;
		color: rgba(255,255,255,0.65);
	}

	.quick-summary-item strong {
		font-size: 13px;
		font-weight: 600;
		color: rgba(255,255,255,0.93);
		line-height: 1.3;
		word-break: break-word;
	}

	.quick-summary-item.done {
		border-color: rgba(40, 167, 69, 0.8);
		box-shadow: inset 0 0 0 1px rgba(40, 167, 69, 0.45);
		background: rgba(40, 167, 69, 0.16);
	}

	.mobile-mini-summary {
		display: none;
	}

	.mini-summary-main {
		font-size: 12px;
		font-weight: 700;
		color: #f8fffb;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.mini-summary-sub {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 8px;
		margin-top: 4px;
		font-size: 11px;
		color: rgba(236, 255, 246, 0.86);
	}

	.quick-date-buttons {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		margin-top: 12px;
	}

	.quick-date-btn {
		border: 1px solid rgba(255,255,255,0.35);
		background: rgba(255,255,255,0.12);
		color: #fff;
		padding: 6px 12px;
		border-radius: 999px;
		font-size: 12px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.quick-date-btn:hover {
		background: rgba(255,255,255,0.25);
		transform: translateY(-1px);
	}

	.quick-date-btn.active {
		background: rgba(40, 167, 69, 0.32);
		border-color: rgba(40, 167, 69, 0.95);
		box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.15);
	}

	/* Select2 Dropdown Styling */
	.select2-dropdown {
		border-radius: 15px !important;
		border: 2px solid #be2623 !important;
	}

	.select2-container--default .select2-selection--single {
		border-radius: 25px !important;
		border: 2px solid rgba(255,255,255,0.3) !important;
		background: rgba(255,255,255,0.1) !important;
		height: 48px !important;
		line-height: 44px !important;
	}

	.select2-container--default .select2-selection--single:focus {
		border-color: #be2623 !important;
	}

	.select2-container--default .select2-selection--single .select2-selection__rendered {
		color: white !important;
		padding-left: 20px !important;
	}

	.select2-container--default .select2-selection--single .select2-selection__arrow {
		height: 44px !important;
		right: 15px !important;
	}

	/* Steps Styling */
	.steps-container {
		padding: 20px 0;
	}

	.step-progress {
		display: flex;
		align-items: center;
		justify-content: center;
		flex-wrap: wrap;
		gap: 10px;
	}

	.step {
		display: flex;
		flex-direction: column;
		align-items: center;
		text-align: center;
		min-width: 80px;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.step:hover .step-number {
		transform: scale(1.1);
	}

	.step.completed {
		cursor: pointer;
	}

	.step.completed:hover .step-number {
		background: linear-gradient(135deg, #20c997, #198754);
		transform: scale(1.06);
		box-shadow: 0 0 0 2px rgba(32, 201, 151, 0.45), 0 10px 24px rgba(25, 135, 84, 0.42);
	}

	.step-number {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		background: rgba(255,255,255,0.3);
		color: white;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: bold;
		margin-bottom: 8px;
		transition: all 0.3s ease;
	}

	.step-title {
		color: white;
		font-size: 12px;
		font-weight: 500;
	}

	.step.active .step-number {
		background: #28a745;
		box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
	}

	.step.completed .step-number {
		background: linear-gradient(135deg, #20c997, #198754);
		box-shadow: 0 0 0 2px rgba(32, 201, 151, 0.35), 0 8px 20px rgba(25, 135, 84, 0.35);
		position: relative;
		color: transparent;
	}

	.step.completed .step-number::after {
		content: '\\2713';
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -52%);
		color: #fff;
		font-size: 16px;
		font-weight: 700;
	}

	.step.completed .step-title {
		color: #d6ffe7;
		font-weight: 700;
	}

	.step-line {
		width: 30px;
		height: 2px;
		background: rgba(255,255,255,0.3);
		margin: 0 5px;
		margin-top: -25px;
	}

	.step-line.completed {
		height: 3px;
		background: linear-gradient(90deg, #20c997, #17a2b8);
	}

	/* Step Content */
	.step-content {
		animation: fadeIn 0.5s ease-in-out;
	}

	@keyframes fadeIn {
		from { opacity: 0; transform: translateX(20px); }
		to { opacity: 1; transform: translateX(0); }
	}

	/* Step Buttons */
	.step-buttons {
		display: flex;
		justify-content: space-between;
		margin-top: 20px;
		gap: 10px;
	}

	.step-lock-hint {
		display: none;
		margin-top: 10px;
		font-size: 13px;
		color: rgba(255,255,255,0.78);
		text-align: center;
	}

	.step-lock-hint.show {
		display: block;
	}

	.btn-prev, .btn-next {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, #be2623, #ff4757);
		color: white;
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		flex: 1;
		max-width: 150px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-prev::before, .btn-next::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0e3746, #2c5aa0);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-prev:hover, .btn-next:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-prev:hover::before, .btn-next:hover::before {
		left: 0;
	}

	.btn-next:disabled {
		background: #6c757d;
		cursor: not-allowed;
		transform: none;
		opacity: 0.55;
		filter: saturate(0.5);
	}

	.btn-next:disabled::before {
		display: none;
	}

	/* Summary Box */
	.summary-box {
		border-left: 4px solid #28a745;
		border-radius: 15px !important;
		background: rgba(255,255,255,0.1) !important;
		backdrop-filter: blur(10px);
	}

	/* Modal Styling */
	.modal-content {
		border-radius: 20px !important;
		border: none !important;
		box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
	}

	.modal-header {
		border-bottom: 1px solid rgba(0,0,0,0.1) !important;
		border-radius: 20px 20px 0 0 !important;
	}

	.modal-footer {
		border-top: 1px solid rgba(0,0,0,0.1) !important;
		border-radius: 0 0 20px 20px !important;
	}

	/* Horários Grid Styling */
	.horarios-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}

	.horario-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
		min-height: 80px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}

	.horario-card:hover {
		transform: translateY(-5px);
		border-color: #be2623;
		box-shadow: 0 10px 25px rgba(190, 38, 35, 0.3);
		background: rgba(255,255,255,0.2);
	}

	.horario-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}

	.horario-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 18px;
		font-weight: 600;
	}

	.horario-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 12px;
	}

	.horario-check {
		position: absolute;
		top: 8px;
		right: 8px;
		width: 20px;
		height: 20px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 10px;
	}

	.horario-card.selected .horario-check {
		display: flex;
	}

	.horario-loading {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
		padding: 18px;
		margin: 16px 0;
		border-radius: 14px;
		background: rgba(255,255,255,0.08);
		border: 1px dashed rgba(255,255,255,0.35);
		color: rgba(255,255,255,0.9);
		font-weight: 600;
	}

	.horario-loading-dot {
		width: 10px;
		height: 10px;
		border-radius: 50%;
		background: #17a2b8;
		animation: horarioPulse 0.9s infinite ease-in-out;
	}

	.horario-loading-dot:nth-child(2) {
		animation-delay: 0.1s;
	}

	.horario-loading-dot:nth-child(3) {
		animation-delay: 0.2s;
	}

	@keyframes horarioPulse {
		0%,
		100% {
			transform: scale(0.7);
			opacity: 0.45;
		}
		50% {
			transform: scale(1);
			opacity: 1;
		}
	}

	/* Legacy support for old horario-item class */
	.horario-item {
		border-radius: 15px !important;
		transition: all 0.3s ease;
	}

	.horario-item:hover {
		transform: translateY(-2px);
		box-shadow: 0 5px 15px rgba(0,0,0,0.2);
	}

	.summary-item {
		margin-bottom: 8px;
		padding: 5px 0;
		border-bottom: 1px solid rgba(255,255,255,0.1);
	}

	.summary-item:last-child {
		border-bottom: none;
	}

	.summary-label {
		font-weight: bold;
		margin-right: 10px;
	}

	/* Responsive */
	@media (max-width: 768px) {
		.steps-container {
			padding: 15px 0;
		}

		.step-progress {
			flex-direction: row;
			justify-content: space-between;
			gap: 5px;
			overflow-x: auto;
			padding: 10px 5px;
		}

		.step {
			min-width: 60px;
			flex-shrink: 0;
		}

		.step-number {
			width: 35px;
			height: 35px;
			font-size: 14px;
			margin-bottom: 5px;
		}

		.step-title {
			font-size: 10px;
			line-height: 1.2;
			max-width: 60px;
			word-wrap: break-word;
		}

		.step-line {
			width: 15px;
			height: 2px;
			margin: 0 2px;
			margin-top: -20px;
			flex-shrink: 0;
		}

		.step-buttons {
			flex-direction: column;
			gap: 15px;
		}

		.btn-prev, .btn-next {
			max-width: none;
			padding: 15px 20px;
			font-size: 16px;
		}

		.step-content h4 {
			font-size: 1.3rem !important;
			margin-bottom: 25px !important;
		}

		.form-group {
			margin-bottom: 20px;
		}

		.form-control {
			font-size: 16px !important;
			padding: 15px 20px !important;
		}

		.step-feedback {
			margin-top: 0;
			font-size: 13px;
			padding: 10px 12px;
		}

		.quick-summary-card {
			display: none;
		}

		.quick-summary-header h5 {
			font-size: 14px;
		}

		.quick-summary-header span {
			font-size: 11px;
		}

		.quick-summary-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 8px;
		}

		.quick-summary-item {
			padding: 9px 10px;
			min-height: 56px;
		}

		.mobile-mini-summary {
			display: block;
			position: fixed;
			left: 10px;
			right: 10px;
			bottom: 10px;
			z-index: 120;
			padding: 10px 12px;
			border-radius: 12px;
			background: rgba(7, 35, 31, 0.92);
			border: 1px solid rgba(106, 221, 170, 0.45);
			box-shadow: 0 10px 26px rgba(0, 0, 0, 0.35);
			backdrop-filter: blur(8px);
		}

		#form-agenda {
			padding-bottom: 84px;
		}

		#step-content-5 {
			padding-bottom: 86px;
		}

		.step-lock-hint {
			font-size: 12px;
		}

		.quick-date-buttons {
			gap: 8px;
		}

		.quick-date-btn {
			font-size: 11px;
			padding: 6px 10px;
		}
	}

	@media (max-width: 576px) {
		.steps-container {
			padding: 10px 0;
		}

		.step-progress {
			gap: 3px;
			padding: 5px;
		}

		.step {
			min-width: 50px;
		}

		.step-number {
			width: 30px;
			height: 30px;
			font-size: 12px;
		}

		.step-title {
			font-size: 9px;
			max-width: 50px;
		}

		.step-line {
			width: 10px;
			margin-top: -18px;
		}

		.quick-summary-grid {
			grid-template-columns: 1fr;
		}

		.mini-summary-main {
			font-size: 11px;
		}

		.mini-summary-sub {
			font-size: 10px;
		}

		.step-content h4 {
			font-size: 1.1rem !important;
		}

		.btn-prev, .btn-next {
			padding: 12px 15px;
			font-size: 14px;
		}
	}

	@media (max-width: 480px) {
		.step-progress {
			gap: 2px;
		}

		.step {
			min-width: 45px;
		}

		.step-number {
			width: 28px;
			height: 28px;
			font-size: 11px;
		}

		.step-title {
			font-size: 8px;
			max-width: 45px;
		}

		.step-line {
			width: 8px;
			margin-top: -16px;
		}

		.professionals-grid {
			grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
			gap: 6px;
		}

		.professional-card {
			padding: 10px;
		}

		.professional-photo {
			width: 45px;
			height: 45px;
			margin-bottom: 6px;
		}

		.professional-info h5 {
			font-size: 11px;
		}

		.professional-info p {
			font-size: 9px;
		}
	}

	/* Final Step Buttons */
	.btn-prev-final {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, #be2623, #ff4757);
		color: white;
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 14px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-prev-final::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0e3746, #2c5aa0);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-prev-final:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-prev-final:hover::before {
		left: 0;
	}

	.btn-confirm {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, #be2623, #ff4757);
		color: white;
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 16px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-confirm::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0e3746, #2c5aa0);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-confirm:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-confirm:hover::before {
		left: 0;
	}

	/* Professional Cards Styling */
	.professionals-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}

	.professional-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
	}

	.professional-card:hover {
		transform: translateY(-5px);
		border-color: #be2623;
		box-shadow: 0 10px 25px rgba(190, 38, 35, 0.3);
		background: rgba(255,255,255,0.2);
	}

	.professional-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}

	.professional-photo {
		width: 80px;
		height: 80px;
		margin: 0 auto 15px;
		border-radius: 50%;
		overflow: hidden;
		border: 3px solid rgba(255,255,255,0.3);
		transition: all 0.3s ease;
	}

	.professional-card:hover .professional-photo {
		border-color: #be2623;
	}

	.professional-card.selected .professional-photo {
		border-color: #28a745;
	}

	.professional-photo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.professional-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 16px;
		font-weight: 600;
	}

	.professional-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 14px;
	}

	.professional-check {
		position: absolute;
		top: 10px;
		right: 10px;
		width: 25px;
		height: 25px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 12px;
	}

	.professional-card.selected .professional-check {
		display: flex;
	}

	/* Mensagem de Sucesso Estilizada */
	.mensagem-container {
		margin: 20px 0;
		padding: 0;
		position: relative;
	}

	.mensagem-aviso {
		background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
		color: white;
		border: none;
		border-radius: 16px;
		padding: 14px 18px;
		margin: 12px auto;
		max-width: 460px;
		box-shadow: 0 8px 20px rgba(30, 64, 175, 0.28);
		font-size: 15px;
		font-weight: 600;
		text-align: center;
	}

	.mensagem-sucesso {
		background: linear-gradient(135deg, #28a745, #20c997);
		color: white;
		border: none;
		border-radius: 20px;
		padding: 25px 30px;
		margin: 20px auto;
		max-width: 500px;
		box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		position: relative;
		overflow: hidden;
		transform: scale(0);
		animation: successPulse 0.6s ease-out forwards;
		backdrop-filter: blur(10px);
	}

	.mensagem-sucesso::before {
		content: '';
		position: absolute;
		top: -50%;
		left: -50%;
		width: 200%;
		height: 200%;
		background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
		transform: rotate(45deg);
		animation: shine 2s infinite;
	}

	.mensagem-sucesso .icone-sucesso {
		display: inline-block;
		margin-right: 12px;
		font-size: 24px;
		animation: bounce 1s infinite alternate;
	}

	.mensagem-sucesso .texto-sucesso {
		display: inline-block;
		vertical-align: middle;
		position: relative;
		z-index: 2;
	}

	@keyframes successPulse {
		0% {
			transform: scale(0);
			opacity: 0;
		}
		50% {
			transform: scale(1.1);
			opacity: 1;
		}
		100% {
			transform: scale(1);
			opacity: 1;
		}
	}

	@keyframes bounce {
		0% {
			transform: translateY(0);
		}
		100% {
			transform: translateY(-5px);
		}
	}

	@keyframes shine {
		0% {
			transform: translateX(-100%) translateY(-100%) rotate(45deg);
		}
		100% {
			transform: translateX(100%) translateY(100%) rotate(45deg);
		}
	}

	.mensagem-sucesso:hover {
		transform: scale(1.05);
		box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
		transition: all 0.3s ease;
	}

	/* Mensagem de Erro Estilizada */
	.mensagem-erro {
		background: linear-gradient(135deg, #dc3545, #e74c3c);
		color: white;
		border: none;
		border-radius: 20px;
		padding: 25px 30px;
		margin: 20px auto;
		max-width: 500px;
		box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
		font-size: 18px;
		font-weight: 600;
		text-align: center;
		position: relative;
		overflow: hidden;
		transform: scale(0);
		animation: errorShake 0.6s ease-out forwards;
		backdrop-filter: blur(10px);
	}

	.mensagem-erro .icone-erro {
		display: inline-block;
		margin-right: 12px;
		font-size: 24px;
		animation: shake 0.5s infinite;
	}

	.mensagem-erro .texto-erro {
		display: inline-block;
		vertical-align: middle;
		position: relative;
		z-index: 2;
	}

	@keyframes errorShake {
		0% {
			transform: scale(0) translateX(0);
			opacity: 0;
		}
		50% {
			transform: scale(1.1) translateX(-5px);
			opacity: 1;
		}
		100% {
			transform: scale(1) translateX(0);
			opacity: 1;
		}
	}

	@keyframes shake {
		0%, 100% {
			transform: translateX(0);
		}
		25% {
			transform: translateX(-3px);
		}
		75% {
			transform: translateX(3px);
		}
	}

	/* Confetti Container */
	.confetti-container {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		pointer-events: none;
		z-index: 9999;
		overflow: hidden;
	}

	/* Confetti Animation */
	.confetti {
		position: absolute;
		top: -20px;
		width: 10px;
		height: 10px;
		background: #ffd700;
		animation: confetti-fall 4s linear forwards;
		pointer-events: none;
		border-radius: 50%;
		box-shadow: 0 0 6px rgba(0,0,0,0.3);
	}

	@keyframes confetti-fall {
		0% {
			transform: translateY(-20px) rotate(0deg);
			opacity: 1;
		}
		10% {
			opacity: 1;
		}
		90% {
			opacity: 0.8;
		}
		100% {
			transform: translateY(100vh) rotate(720deg);
			opacity: 0;
		}
	}

	/* Responsive para botões finais */
	@media (max-width: 768px) {
		.btn-prev-final, .btn-confirm {
			padding: 15px 25px;
			font-size: 16px;
			min-height: 50px;
			width: 100%;
			margin-bottom: 10px;
		}

		.professionals-grid {
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 10px;
		}

		.professional-card {
			padding: 15px;
		}

		.professional-photo {
			width: 60px;
			height: 60px;
			margin-bottom: 10px;
		}

		.professional-info h5 {
			font-size: 14px;
		}

		.professional-info p {
			font-size: 12px;
		}

		.mensagem-sucesso {
			padding: 20px 25px;
			font-size: 16px;
			margin: 15px 10px;
		}

		.mensagem-sucesso .icone-sucesso {
			font-size: 20px;
			margin-right: 8px;
		}

		.mensagem-erro {
			padding: 20px 25px;
			font-size: 16px;
			margin: 15px 10px;
		}

		.mensagem-erro .icone-erro {
			font-size: 20px;
			margin-right: 8px;
		}

		/* Horários Grid Mobile */
		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
			gap: 8px;
			margin: 15px 0;
		}

		.horario-card {
			padding: 15px 10px;
			min-height: 60px;
		}

		.horario-info h5 {
			font-size: 16px;
		}

		.horario-info p {
			font-size: 11px;
		}

		.horario-check {
			width: 18px;
			height: 18px;
			font-size: 9px;
			top: 6px;
			right: 6px;
		}

		/* Legacy support */
		.horario-item {
			padding: 12px 8px !important;
			font-size: 14px !important;
			min-height: 45px;
			border-radius: 10px !important;
		}

		/* Summary Box Mobile */
		.summary-box {
			margin: 15px 0 !important;
			padding: 20px !important;
			border-radius: 10px !important;
		}

		.summary-item {
			margin-bottom: 12px;
			padding: 10px 0;
			font-size: 16px;
		}

		.summary-label {
			display: block;
			margin-bottom: 5px;
			font-size: 14px;
			color: rgba(255,255,255,0.8);
		}

		/* Select2 Mobile */
		.select2-container--default .select2-selection--single {
			height: 50px !important;
			line-height: 46px !important;
			font-size: 16px !important;
		}
	}

	@media (max-width: 576px) {
		.btn-prev-final, .btn-confirm {
			padding: 12px 20px;
			font-size: 14px;
			min-height: 45px;
		}

		.professionals-grid, .services-grid {
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 8px;
		}

		.professional-card, .service-card {
			padding: 12px;
		}

		.professional-photo, .service-photo {
			width: 50px;
			height: 50px;
			margin-bottom: 8px;
		}

		.professional-info h5, .service-info h5 {
			font-size: 12px;
		}

		.professional-info p, .service-info p {
			font-size: 10px;
		}

		.professional-check, .service-check {
			width: 20px;
			height: 20px;
			font-size: 10px;
			top: 5px;
			right: 5px;
		}

		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
			gap: 6px;
		}

		.horario-card {
			padding: 12px 8px;
			min-height: 50px;
		}

		.horario-info h5 {
			font-size: 14px;
		}

		.horario-info p {
			font-size: 10px;
		}

		.horario-check {
			width: 16px;
			height: 16px;
			font-size: 8px;
			top: 5px;
			right: 5px;
		}

		/* Legacy support */
		.horario-item {
			padding: 10px 6px !important;
			font-size: 13px !important;
			min-height: 40px;
		}

		.summary-box {
			padding: 15px !important;
		}

		.summary-item {
			font-size: 14px;
			margin-bottom: 10px;
		}

		.summary-label {
			font-size: 13px;
		}
	}

	@media (max-width: 480px) {
		.horarios-grid {
			grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
			gap: 5px;
		}

		.horario-card {
			padding: 10px 6px;
			min-height: 45px;
		}

		.horario-info h5 {
			font-size: 12px;
		}

		.horario-info p {
			font-size: 9px;
		}

		.horario-check {
			width: 14px;
			height: 14px;
			font-size: 7px;
			top: 4px;
			right: 4px;
		}

		/* Legacy support */
		.horario-item {
			padding: 8px 4px !important;
			font-size: 12px !important;
			min-height: 35px;
		}

		.summary-item {
			font-size: 13px;
		}
	}

	.btn-new {
		display: inline-block;
		padding: 12px 30px;
		background: linear-gradient(45deg, #be2623, #ff4757);
		color: white;
		text-decoration: none;
		border-radius: 25px !important;
		font-weight: 600;
		transition: all 0.3s ease;
		position: relative;
		overflow: hidden;
		border: none;
		cursor: pointer;
		font-size: 16px;
		min-height: 48px;
		text-align: center;
		box-sizing: border-box;
	}

	.btn-new::before {
		content: '';
		position: absolute;
		top: 0;
		left: -100%;
		width: 100%;
		height: 100%;
		background: linear-gradient(45deg, #0e3746, #2c5aa0);
		transition: left 0.3s ease;
		z-index: -1;
	}

	.btn-new:hover {
		color: white !important;
		text-decoration: none;
		transform: translateY(-2px);
	}

	.btn-new:hover::before {
		left: 0;
	}



	.button-row {
		align-items: center;
	}

	/* Summary improvements */
	.summary-box {
		border-left: 4px solid #28a745;
		box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	}

	.summary-item {
		margin-bottom: 10px;
		padding: 8px 0;
		border-bottom: 1px solid rgba(255,255,255,0.1);
		font-size: 15px;
	}

	.summary-item:last-child {
		border-bottom: none;
		margin-bottom: 0;
	}

	.summary-label {
		font-weight: bold;
		margin-right: 10px;
		color: #ffffff;
	}

	/* Services Grid Styles */
	.services-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 15px;
		margin: 20px 0;
	}
	
	.service-card {
		background: rgba(255,255,255,0.1);
		border: 2px solid rgba(255,255,255,0.3);
		border-radius: 15px;
		padding: 20px;
		text-align: center;
		cursor: pointer;
		transition: all 0.3s ease;
		position: relative;
		backdrop-filter: blur(10px);
	}
	
	.service-card:hover {
		transform: translateY(-5px);
		border-color: #be2623;
		box-shadow: 0 10px 25px rgba(190, 38, 35, 0.3);
		background: rgba(255,255,255,0.2);
	}
	
	.service-card.selected {
		border-color: #28a745;
		background: rgba(40, 167, 69, 0.2);
		box-shadow: 0 0 20px rgba(40, 167, 69, 0.4);
	}
	
	.service-photo {
		width: 80px;
		height: 80px;
		margin: 0 auto 15px;
		border-radius: 50%;
		overflow: hidden;
		border: 3px solid rgba(255,255,255,0.3);
		transition: all 0.3s ease;
	}
	
	.service-card:hover .service-photo {
		border-color: #be2623;
	}
	
	.service-card.selected .service-photo {
		border-color: #28a745;
	}
	
	.service-photo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	
	.service-info h5 {
		color: white;
		margin: 0 0 5px 0;
		font-size: 16px;
		font-weight: 600;
	}
	
	.service-info p {
		color: rgba(255,255,255,0.8);
		margin: 0;
		font-size: 14px;
	}
	
	.service-check {
		position: absolute;
		top: 10px;
		right: 10px;
		width: 25px;
		height: 25px;
		background: #28a745;
		border-radius: 50%;
		display: none;
		align-items: center;
		justify-content: center;
		color: white;
		font-size: 12px;
	}
	
	.service-card.selected .service-check {
		display: flex;
	}
	
	.service-check i {
		color: white;
		font-size: 12px;
	}
	
	/* Loyalty Card Responsive Styles */
	@media (max-width: 768px) {
		.services-grid {
			grid-template-columns: 1fr;
			gap: 15px;
		}
		
		.service-card {
			padding: 15px;
			min-height: 120px;
			gap: 12px;
		}
		
		.service-photo img {
			width: 60px;
			height: 60px;
		}
		
		.service-info h5 {
			font-size: 14px;
		}
		
		.service-info p {
			font-size: 16px;
		}
		
		.loyalty-card-container {
			margin: 15px 0 !important;
			padding: 20px !important;
		}
		
		.loyalty-cards-grid {
			gap: 6px !important;
		}
		
		.loyalty-card {
			width: 40px !important;
			height: 40px !important;
		}
		
		.loyalty-card span {
			font-size: 16px !important;
		}
	}
	
	@media (max-width: 480px) {
		.services-grid {
			grid-template-columns: 1fr;
			gap: 12px;
		}
		
		.service-card {
			padding: 12px;
			min-height: 100px;
			gap: 10px;
		}
		
		.service-photo img {
			width: 50px;
			height: 50px;
		}
		
		.service-info h5 {
			font-size: 13px;
		}
		
		.service-info p {
			font-size: 14px;
		}
		
		.loyalty-card-container {
			padding: 15px !important;
		}
		
		.loyalty-card {
			width: 35px !important;
			height: 35px !important;
		}
		
		.loyalty-card span {
			font-size: 14px !important;
		}
	}
</style>  



<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
			
		});

		if (typeof $.fn.mask === 'function') {
			$('#telefone').mask('(00) 00000-0000');
		}
		
		// Initialize steps
		currentStep = 1;
		updateStepDisplay();
		sincronizarDataRapida();
		updateQuickSummary();
		updateStepActionState();

		$('#nome, #telefone, #data').on('input change', function() {
			hideStepFeedback();
			updateQuickSummary();
			updateStepActionState();
		});
		
		// Add click events to steps
		for (let i = 1; i <= 5; i++) {
			document.getElementById(`step-${i}`).addEventListener('click', function() {
				goToStep(i);
			});
		}
	});

	// Step Navigation Variables
	let currentStep = 1;
	const totalSteps = 5;

	function setQuickSummaryValue(fieldId, itemId, value, fallback) {
		const field = document.getElementById(fieldId);
		const item = document.getElementById(itemId);
		if (!field || !item) {
			return;
		}

		const hasValue = Boolean(value && String(value).trim() !== '');
		field.textContent = hasValue ? String(value).trim() : fallback;
		item.classList.toggle('done', hasValue);
	}

	function updateQuickSummary() {
		const nome = document.getElementById('nome')?.value || '';
		const telefone = document.getElementById('telefone')?.value || '';
		const data = document.getElementById('data')?.value || '';
		const funcionarioNome = document.querySelector('.professional-card.selected h5')?.textContent || '';
		const servicoNome = document.querySelector('.service-card.selected h5')?.textContent || '';
		const horaSelecionada = obterHoraSelecionada();

		let dataFormatada = '';
		if (data) {
			const dataObj = new Date(`${data}T12:00:00`);
			if (!Number.isNaN(dataObj.getTime())) {
				dataFormatada = dataObj.toLocaleDateString('pt-BR');
			}
		}

		setQuickSummaryValue('quick-nome', 'quick-item-nome', nome, 'Nao informado');
		setQuickSummaryValue('quick-telefone', 'quick-item-telefone', telefone, 'Nao informado');
		setQuickSummaryValue('quick-data', 'quick-item-data', dataFormatada, 'Nao selecionada');
		setQuickSummaryValue('quick-funcionario', 'quick-item-funcionario', funcionarioNome, 'Nao selecionado');
		setQuickSummaryValue('quick-hora', 'quick-item-hora', horaSelecionada, 'Nao selecionado');
		setQuickSummaryValue('quick-servico', 'quick-item-servico', servicoNome, 'Nao selecionado');
		updateMobileMiniSummary(nome, dataFormatada, horaSelecionada, servicoNome);
	}

	function updateMobileMiniSummary(nome, dataFormatada, horaSelecionada, servicoNome) {
		const miniMain = document.getElementById('mini-summary-main');
		const miniStep = document.getElementById('mini-summary-step');
		const miniNext = document.getElementById('mini-summary-next');
		if (!miniMain || !miniStep || !miniNext) {
			return;
		}

		const nomeLimpo = (nome || '').trim();
		const servicoLimpo = (servicoNome || '').trim();
		const partes = [];
		if (dataFormatada) {
			partes.push(dataFormatada);
		}
		if (horaSelecionada) {
			partes.push(horaSelecionada);
		}
		if (servicoLimpo) {
			partes.push(servicoLimpo);
		}

		if (nomeLimpo) {
			miniMain.textContent = partes.length > 0 ? `${nomeLimpo} - ${partes.join(' | ')}` : nomeLimpo;
		} else {
			miniMain.textContent = 'Preencha os dados iniciais para comecar.';
		}

		miniStep.textContent = `Etapa ${currentStep} de ${totalSteps}`;

		if (currentStep >= totalSteps) {
			miniNext.textContent = 'Pronto para confirmar';
			return;
		}

		const validacaoAtual = getStepValidation(currentStep);
		miniNext.textContent = validacaoAtual.ok ? 'Pode avancar' : validacaoAtual.message;
	}

	function getStepValidation(step) {
		switch(step) {
			case 1: {
				const telefone = document.getElementById('telefone')?.value.trim() || '';
				const nome = document.getElementById('nome')?.value.trim() || '';
				const telefoneNumerico = telefone.replace(/\D/g, '');

				if (!telefone || !nome) {
					return { ok: false, message: 'Preencha nome e telefone para continuar.' };
				}

				if (telefoneNumerico.length < 10) {
					return { ok: false, message: 'Informe um telefone valido com DDD.' };
				}

				return { ok: true };
			}

			case 2: {
				const data = document.getElementById('data')?.value || '';
				const funcionario = document.getElementById('funcionario')?.value || '';
				if (!data || !funcionario) {
					return { ok: false, message: 'Selecione data e profissional para ver horarios disponiveis.' };
				}
				return { ok: true };
			}

			case 3: {
				const horario = document.getElementById('hora')?.value || '';
				if (!horario) {
					return { ok: false, message: 'Escolha um horario antes de prosseguir.' };
				}
				return { ok: true };
			}

			case 4: {
				const servico = document.getElementById('servico')?.value || '';
				if (!servico) {
					return { ok: false, message: 'Selecione um servico para concluir o agendamento.' };
				}
				return { ok: true };
			}

			default:
				return { ok: true };
		}
	}

	function updateStepActionState() {
		for (let step = 1; step <= 4; step++) {
			const stepContent = document.getElementById(`step-content-${step}`);
			const hint = document.getElementById(`hint-step-${step}`);
			if (!stepContent) {
				continue;
			}

			const nextBtn = stepContent.querySelector('.btn-next');
			const validation = getStepValidation(step);
			const isLocked = !validation.ok;

			if (nextBtn) {
				nextBtn.disabled = isLocked;
				nextBtn.classList.toggle('locked', isLocked);
				nextBtn.setAttribute('aria-disabled', isLocked ? 'true' : 'false');
				if (isLocked) {
					nextBtn.setAttribute('title', validation.message || 'Complete os campos obrigatorios.');
				} else {
					nextBtn.removeAttribute('title');
				}
			}

			if (hint) {
				hint.classList.toggle('show', isLocked && currentStep === step);
			}
		}
	}

	function showStepFeedback(message, type = 'error') {
		const feedback = document.getElementById('step-feedback');
		if (!feedback) {
			return;
		}

		feedback.className = `step-feedback ${type}`;
		feedback.textContent = message;
		feedback.style.display = 'block';
		feedback.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	function hideStepFeedback() {
		const feedback = document.getElementById('step-feedback');
		if (!feedback) {
			return;
		}

		feedback.style.display = 'none';
		feedback.textContent = '';
		feedback.className = 'step-feedback';
	}

	function selecionarDataRapida(button, dias) {
		const inputData = document.getElementById('data');
		if (!inputData) {
			return;
		}

		const dataBase = new Date();
		dataBase.setHours(12, 0, 0, 0);
		dataBase.setDate(dataBase.getDate() + dias);

		const yyyy = dataBase.getFullYear();
		const mm = String(dataBase.getMonth() + 1).padStart(2, '0');
		const dd = String(dataBase.getDate()).padStart(2, '0');
		inputData.value = `${yyyy}-${mm}-${dd}`;

		document.querySelectorAll('.quick-date-btn').forEach((btn) => btn.classList.remove('active'));
		button.classList.add('active');

		mudarFuncionario();
		hideStepFeedback();
	}

	function sincronizarDataRapida() {
		const inputData = document.getElementById('data');
		if (!inputData) {
			return;
		}

		const hoje = new Date();
		hoje.setHours(12, 0, 0, 0);
		const selecionada = new Date(`${inputData.value}T12:00:00`);
		const diferencaMs = selecionada.getTime() - hoje.getTime();
		const diferencaDias = Math.round(diferencaMs / (1000 * 60 * 60 * 24));

		document.querySelectorAll('.quick-date-btn').forEach((btn, index) => {
			const dias = index === 0 ? 0 : index === 1 ? 1 : 7;
			btn.classList.toggle('active', diferencaDias === dias);
		});
	}

	function obterHoraSelecionada() {
		const horaInput = document.getElementById('hora');
		if (horaInput && horaInput.value) {
			return horaInput.value;
		}

		const cardHorario = document.querySelector('.horario-card.selected .horario-info h5');
		return cardHorario ? cardHorario.textContent.trim() : '';
	}

	// Step Navigation Functions
	function nextStep(step) {
		if (validateStep(step)) {
			if (step < totalSteps) {
				currentStep = step + 1;
				hideStepFeedback();
				updateStepDisplay();
				if (currentStep === 5) {
					updateSummary();
				}
			}
		}
	}

	function prevStep(step) {
		if (step > 1) {
			currentStep = step - 1;
			hideStepFeedback();
			updateStepDisplay();
		}
	}

	function updateStepDisplay() {
		// Hide all step contents
		for (let i = 1; i <= totalSteps; i++) {
			document.getElementById(`step-content-${i}`).style.display = 'none';
			document.getElementById(`step-${i}`).classList.remove('active', 'completed');
		}

		document.querySelectorAll('.step-line').forEach((line) => {
			line.classList.remove('completed');
		}

		// Show current step content
		document.getElementById(`step-content-${currentStep}`).style.display = 'block';
		document.getElementById(`step-${currentStep}`).classList.add('active');

		// Mark completed steps
		for (let i = 1; i < currentStep; i++) {
			document.getElementById(`step-${i}`).classList.add('completed');
			document.querySelectorAll('.step-line')[i-1]?.classList.add('completed');
		}

		updateStepActionState();
		updateQuickSummary();
	}

	function validateStep(step) {
		const validation = getStepValidation(step);
		if (!validation.ok) {
			showStepFeedback(validation.message || 'Complete os dados obrigatorios para continuar.');
			updateStepActionState();
			return false;
		}

		return true;
	}

	function updateSummary() {
		const nome = document.getElementById('nome').value;
		const telefone = document.getElementById('telefone').value;
		const data = document.getElementById('data').value;
		
		// Obter nome do profissional selecionado
		const funcionarioCard = document.querySelector('.professional-card.selected');
		const funcionarioNome = funcionarioCard ? funcionarioCard.querySelector('h5').textContent : '';
		
		// Obter nome do serviço selecionado
		const servicoCard = document.querySelector('.service-card.selected');
		const servicoNome = servicoCard ? servicoCard.querySelector('h5').textContent : '';
		
		// Obter horário selecionado
		const horaInput = document.getElementById('hora');
		const horarioId = horaInput ? horaInput.value : '';
		const horarioCard = document.querySelector('.horario-card.selected');
		const horario = horarioCard ? horarioCard.querySelector('h5').textContent : horarioId;

		// Format date
		const dataFormatada = data ? new Date(data + 'T00:00:00').toLocaleDateString('pt-BR') : '';

		const summaryHTML = `
			<div class="summary-item">
				<span class="summary-label">Nome:</span> ${nome}
			</div>
			<div class="summary-item">
				<span class="summary-label">Telefone:</span> ${telefone}
			</div>
			<div class="summary-item">
				<span class="summary-label">Data:</span> ${dataFormatada}
			</div>
			<div class="summary-item">
				<span class="summary-label">Profissional:</span> ${funcionarioNome}
			</div>
			<div class="summary-item">
				<span class="summary-label">Horário:</span> ${horario}
			</div>
			<div class="summary-item">
				<span class="summary-label">Serviço:</span> ${servicoNome}
			</div>
		`;

		document.getElementById('summary-content').innerHTML = summaryHTML;
		updateQuickSummary();
		updateStepActionState();
	}

	function goToStep(targetStep) {
		// Only allow navigation to completed steps or the next step
		if (targetStep <= currentStep || targetStep === currentStep + 1) {
			// Validate current step before moving forward
			if (targetStep > currentStep && !validateStep(currentStep)) {
				return;
			}
			
			currentStep = targetStep;
			hideStepFeedback();
			updateStepDisplay();
			
			if (currentStep === 5) {
				updateSummary();
			}
		}
	}
</script>


<script type="text/javascript">
	
	function mudarFuncionario(){
		var funcionario = $('#funcionario').val();
		var data = $('#data').val();
		var hora = $('#hora_rec').val();

		hideStepFeedback();
		sincronizarDataRapida();

		if(!funcionario || !data){
			$('#listar-horarios').html('<div class="horario-loading"><span>Selecione um profissional para listar os horarios.</span></div>');
			updateQuickSummary();
			updateStepActionState();
			return;
		}

		listarHorarios(funcionario, data, hora);
		listarFuncionario();	
		updateQuickSummary();
		updateStepActionState();

	}

	function selecionarProfissional(id, nome) {
		// Remove seleção anterior
		$('.professional-card').removeClass('selected');
		
		// Adiciona seleção ao card clicado
		$('[data-id="' + id + '"]').addClass('selected');
		
		// Define o valor no campo hidden
		$('#funcionario').val(id);
		
		// Chama a função original para atualizar horários
		mudarFuncionario();
		updateSummary();

		if (currentStep === 2) {
			setTimeout(function() {
				nextStep(2);
			}, 150);
		}
	}
	
	function selecionarServico(id, nome) {
		// Remove seleção anterior
		$('.service-card').removeClass('selected');
		
		// Adiciona seleção ao card clicado
		$('.service-card[data-id="' + id + '"]').addClass('selected');
		
		// Define o valor no campo hidden
		$('#servico').val(id);
		
		// Chama a função para atualizar o nome do serviço
		mudarServico();
		updateSummary();

		if (currentStep === 4) {
			setTimeout(function() {
				nextStep(4);
			}, 150);
		}
	}
</script>



<script type="text/javascript">
	function listarHorarios(funcionario, data, hora){	
		$('#listar-horarios').html('<div class="horario-loading"><span class="horario-loading-dot"></span><span class="horario-loading-dot"></span><span class="horario-loading-dot"></span><span>Buscando horarios disponiveis...</span></div>');

		
		$.ajax({
			url: "ajax/listar-horarios.php",
			method: 'POST',
			data: {funcionario, data, hora},
			dataType: "text",

			success:function(result){
				$("#listar-horarios").html(result);
				if (!result || !result.trim()) {
					$("#listar-horarios").html('<div class="horario-loading"><span>Nenhum horario encontrado para esta data.</span></div>');
				}
				updateQuickSummary();
				updateStepActionState();
			},
			error:function(){
				$("#listar-horarios").html('<div class="horario-loading"><span>Nao foi possivel carregar os horarios. Tente novamente.</span></div>');
				updateStepActionState();
			}
		});
	}
</script>







<script type="text/javascript">
	
	function salvar(){
		$('#id').val('');
			}
</script>




<script>

	$("#form-agenda").submit(function (e) {
		e.preventDefault();


		var formData = new FormData(this);
		var $submitBtn = $('#form-agenda button[type="submit"]');

		$('#mensagem').stop(true, true).show();
		$('#mensagem').removeClass().addClass('mensagem-container');
		$('#mensagem').html('<div class="mensagem-aviso"><span class="texto-aviso">Processando seu agendamento...</span></div>');
		$submitBtn.prop('disabled', true);

		$.ajax({
			url: "ajax/agendar.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem').stop(true, true).show();
				$('#mensagem').text('');
				$('#mensagem').removeClass();
				if (mensagem.trim() == "Agendado com Sucesso") {
				// Criar confetti
				criarConfetti();
				
				// Aplicar estilo de sucesso
				$('#mensagem').removeClass().addClass('mensagem-container');
				$('#mensagem').html('<div class="mensagem-sucesso"><span class="icone-sucesso">🎉</span><span class="texto-sucesso">✅ ' + mensagem + '</span></div>');
				
				// Auto-hide após 5 segundos
				setTimeout(function() {
					$('#mensagem').fadeOut(500);
				}, 5000);

					var nome = $('#nome').val();
					var data = $('#data').val();
					var hora = obterHoraSelecionada();
					var obs = $('#obs').val();
					var nome_func = $('#nome_func').val();
					var nome_serv = $('#nome_serv').val();

					var dataF = data.split("-");
					var dia = dataF[2];
					var mes = dataF[1];
					var ano = dataF[0];
					var dataFormatada = dia + '/' + mes + '/' + ano;

					var horaFormatada = hora;
					if (hora && hora.indexOf(':') !== -1) {
						var horaF = hora.split(':');
						var horaH = horaF[0];
						var horaM = horaF[1] || '00';
						horaFormatada = horaH + ':' + horaM;
					}

					var msg_agendamento = "<?=$msg_agendamento?>";

					if(msg_agendamento == 'Sim'){

				let a= document.createElement('a');
			          a.target= '_blank';
				          a.href= 'https://api.whatsapp.com/send?1=pt_BR&phone=<?=$tel_whatsapp?>&text= _Novo Agendamento_ %0A Funcionário: *' + nome_func + '* %0A Serviço: *' + nome_serv + '* %0A Data: *' + dataFormatada + '* %0A Hora: *' + horaFormatada + '* %0A Cliente: *' + nome + '*  %0A %0A ' + obs;
			          a.click();
			          return;		

			      }


				}


				else if (mensagem.trim() == "Editado com Sucesso") {
				// Criar confetti
				criarConfetti();
				
				// Aplicar estilo de sucesso
				$('#mensagem').removeClass().addClass('mensagem-container');
				$('#mensagem').html('<div class="mensagem-sucesso"><span class="icone-sucesso">✏️</span><span class="texto-sucesso">✅ ' + mensagem + '</span></div>');
				
				// Auto-hide após 5 segundos
				setTimeout(function() {
					$('#mensagem').fadeOut(500);
				}, 5000);

					var nome = $('#nome').val();
					var data = $('#data').val();
					var hora = obterHoraSelecionada();
					var obs = $('#obs').val();
					var nome_func = $('#nome_func').val();
					var nome_serv = $('#nome_serv').val();

					var dataF = data.split("-");
					var dia = dataF[2];
					var mes = dataF[1];
					var ano = dataF[0];
					var dataFormatada = dia + '/' + mes + '/' + ano;

					var horaFormatada = hora;
					if (hora && hora.indexOf(':') !== -1) {
						var horaF = hora.split(':');
						var horaH = horaF[0];
						var horaM = horaF[1] || '00';
						horaFormatada = horaH + ':' + horaM;
					}

					var msg_agendamento = "<?=$msg_agendamento?>";

					if(msg_agendamento == 'Sim'){

				let a= document.createElement('a');
			          a.target= '_blank';
				          a.href= 'https://api.whatsapp.com/send?1=pt_BR&phone=<?=$tel_whatsapp?>&text= *Atenção:* _Agendamento Editado_ %0A Funcionário: *' + nome_func + '* %0A Serviço: *' + nome_serv + '* %0A Data: *' + dataFormatada + '* %0A Hora: *' + horaFormatada + '* %0A Cliente: *' + nome + '*  %0A %0A ' + obs + '';
			          a.click();
			          return;		

			      }


				}


				 else {
					// Aplicar estilo de erro
					$('#mensagem').removeClass().addClass('mensagem-container');
					$('#mensagem').html('<div class="mensagem-erro"><span class="icone-erro">⚠️</span><span class="texto-erro">' + mensagem + '</span></div>');
					
					// Auto-hide após 7 segundos
					setTimeout(function() {
						$('#mensagem').fadeOut(500);
					}, 7000);
				}

			},

			error: function (xhr) {
				var mensagemErro = 'Nao foi possivel concluir o agendamento agora. Tente novamente em alguns instantes.';
				if (xhr && xhr.responseText && xhr.responseText.trim()) {
					mensagemErro = xhr.responseText.trim();
				}

				$('#mensagem').stop(true, true).show();
				$('#mensagem').removeClass().addClass('mensagem-container');
				$('#mensagem').html('<div class="mensagem-erro"><span class="icone-erro">⚠️</span><span class="texto-erro">' + mensagemErro + '</span></div>');
			},

			complete: function () {
				$submitBtn.prop('disabled', false);
			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});

</script>













<script type="text/javascript">
	function listarFuncionario(){	
		var func = $("#funcionario").val();
		
		$.ajax({
			url: "ajax/listar-funcionario.php",
			method: 'POST',
			data: {func},
			dataType: "text",

			success:function(result){
				$("#nome_func").val(result);
			}
		});
	}
</script>


<script type="text/javascript">
	function mudarServico(){	
		var serv = $("#servico").val();
		
		$.ajax({
			url: "ajax/listar-servico.php",
			method: 'POST',
			data: {serv},
			dataType: "text",

			success:function(result){
				$("#nome_serv").val(result);
			}
		});
	}
</script>
