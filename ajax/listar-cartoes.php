<?php 
require_once("../sistema/conexao.php");

$telefone = @$_POST['tel'];

$query = $pdo->query("SELECT * FROM clientes where telefone LIKE '$telefone' ");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
if(@count($res) > 0){
	$cartoes = $res[0]['cartoes'];
	$id_cliente = $res[0]['id'];
	


?>


<div class="loyalty-card-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 15px; margin: 20px 0; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
	<div class="loyalty-header" style="text-align: center; margin-bottom: 20px;">
		<h4 style="color: white; margin: 0; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">🎯 Cartão Fidelidade</h4>
		<p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">Colete <?php echo $quantidade_cartoes ?> carimbos e ganhe um serviço grátis!</p>
	</div>
	
	<div class="loyalty-cards-grid" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 20px;">
		<?php 
		for($i=1; $i<=$quantidade_cartoes; $i++){ 
			if($cartoes >= $i){
				$cardClass = 'completed';
				$cardIcon = '✅';
				$cardBg = 'linear-gradient(45deg, #28a745, #20c997)';
				$cardShadow = '0 4px 15px rgba(40, 167, 69, 0.4)';
			}else{
				$cardClass = 'pending';
				$cardIcon = '⭕';
				$cardBg = 'rgba(255,255,255,0.2)';
				$cardShadow = '0 2px 8px rgba(0,0,0,0.1)';
			}
		?>
		<div class="loyalty-card <?php echo $cardClass ?>" style="
			width: 45px; 
			height: 45px; 
			background: <?php echo $cardBg ?>; 
			border-radius: 50%; 
			display: flex; 
			align-items: center; 
			justify-content: center; 
			box-shadow: <?php echo $cardShadow ?>;
			transition: all 0.3s ease;
			border: 2px solid rgba(255,255,255,0.3);
			position: relative;
			overflow: hidden;
		">
			<span style="font-size: 18px; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));"><?php echo $cardIcon ?></span>
			<?php if($cartoes >= $i): ?>
			<div style="position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; background: #ffd700; border-radius: 50%; border: 1px solid white;"></div>
			<?php endif; ?>
		</div>
		<?php } ?>
	</div>
	
	<div class="loyalty-progress" style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 12px; text-align: center;">
		<div class="progress-bar" style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; margin-bottom: 8px; overflow: hidden;">
			<div style="background: linear-gradient(90deg, #28a745, #20c997); height: 100%; width: <?php echo ($cartoes/$quantidade_cartoes)*100 ?>%; border-radius: 4px; transition: width 0.5s ease;"></div>
		</div>
		<p style="color: white; margin: 0; font-weight: bold; font-size: 14px; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
			<?php echo $cartoes ?> de <?php echo $quantidade_cartoes ?> carimbos coletados
			<?php if($cartoes >= $quantidade_cartoes): ?>
				<br><span style="color: #ffd700; font-size: 12px;">🎉 Parabéns! Você ganhou um serviço grátis!</span>
			<?php else: ?>
				<br><span style="color: rgba(255,255,255,0.8); font-size: 12px;">Faltam <?php echo $quantidade_cartoes - $cartoes ?> carimbos para o próximo prêmio</span>
			<?php endif; ?>
		</p>
	</div>
</div>


<?php } ?>