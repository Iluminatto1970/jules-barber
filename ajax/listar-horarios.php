<?php 
require_once("../sistema/conexao.php");
@session_start();
$usuario = @$_SESSION['id'];

$funcionario = @$_POST['funcionario'];
$data = @$_POST['data'];
$hora_rec = @$_POST['hora'];



if($funcionario == ""){
	
	exit();
}


$diasemana = array("Domingo", "Segunda-Feira", "Terça-Feira", "Quarta-Feira", "Quinta-Feira", "Sexta-Feira", "Sabado");
$diasemana_numero = date('w', strtotime($data));
$dia_procurado = $diasemana[$diasemana_numero];

//percorrer os dias da semana que ele trabalha
$query = $pdo->query("SELECT * FROM dias where funcionario = '$funcionario' and dia = '$dia_procurado'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) == 0){
		echo 'Este Funcionário não trabalha neste Dia!';
	exit();
}

?>
<div class="horarios-grid">
	<?php 
	$query = $pdo->query("SELECT * FROM horarios where funcionario = '$funcionario' ORDER BY horario asc");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);
	if($total_reg > 0){
		for($i=0; $i < $total_reg; $i++){
			foreach ($res[$i] as $key => $value){}
			$hora = $res[$i]['horario'];
				$horaF = date("H:i", strtotime($hora));

				//validar horario
				$query2 = $pdo->query("SELECT * FROM agendamentos where data = '$data' and hora = '$hora' and funcionario = '$funcionario'");
				$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
				$total_reg2 = @count($res2);
				
				$is_occupied = $total_reg2 > 0 && strtotime($hora_rec) != strtotime($hora);
				$is_selected = strtotime($hora_rec) == strtotime($hora);
				
				if($is_occupied){
					// Horário ocupado - mostrar como desabilitado
					?>

					<div class="horario-card occupied" style="opacity: 0.5; cursor: not-allowed;" onclick="return false;">
						<div class="horario-info">
							<h5><?php echo $horaF ?></h5>
							<p>Ocupado</p>
						</div>
					</div>

					<?php 
				}else{
					$hora_agendada = '';
					$texto_hora = '';
					$selected_class = '';

					if(strtotime($hora_rec) == strtotime($hora)){
						$selected_class = 'selected';
					}

					?>

					<div class="horario-card <?php echo $selected_class ?>" onclick="selecionarHorario('<?php echo $hora ?>', this)" data-hora="<?php echo $hora ?>">
						<div class="horario-info">
							<h5><?php echo $horaF ?></h5>
							<p>Disponível</p>
						</div>
						<div class="horario-check"><i class="fa fa-check"></i></div>
					</div>

					<?php 
				}
			}
		}
	?>
	<input type="hidden" id="hora" name="hora" required>
</div>

<script>
function selecionarHorario(hora, elemento) {
	// Remove seleção anterior
	document.querySelectorAll('.horario-card').forEach(card => {
		card.classList.remove('selected');
	});
	
	// Adiciona seleção ao card clicado
	if (elemento) {
		elemento.classList.add('selected');
	}
	
	// Atualiza o campo hidden
	document.getElementById('hora').value = hora;
	const horaRecInput = document.getElementById('hora_rec');
	if (horaRecInput) {
		horaRecInput.value = hora;
	}

	if (typeof hideStepFeedback === 'function') {
		hideStepFeedback();
	}
	
	// Atualiza o resumo
	if (typeof updateSummary === 'function') {
		updateSummary();
	}

	if (typeof updateQuickSummary === 'function') {
		updateQuickSummary();
	}

	if (typeof updateStepActionState === 'function') {
		updateStepActionState();
	}

	if (typeof currentStep !== 'undefined' && currentStep === 3 && typeof nextStep === 'function') {
		setTimeout(function() {
			nextStep(3);
		}, 120);
	}
}
</script>
