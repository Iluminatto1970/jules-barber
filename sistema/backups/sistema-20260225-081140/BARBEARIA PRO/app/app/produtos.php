<?php require_once("cabecalho.php") ?>
<style type="text/css">
	.sub_page .hero_area {
  min-height: auto;
}

/* Estilos melhorados para a seção de produtos */
.products_section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 80px 0;
}

.products_section .heading_container {
  margin-bottom: 60px;
}

.products_section .heading_container h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0e3746;
  margin-bottom: 20px;
  position: relative;
}

.products_section .heading_container h2::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, #be2623, #ff4757);
  border-radius: 2px;
}

.products_section .heading_container p {
  font-size: 1.1rem;
  color: #6c757d;
  line-height: 1.6;
  margin-bottom: 0;
}

.products_container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

.product_card {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  transition: all 0.4s ease;
  overflow: hidden;
  margin-bottom: 30px;
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product_card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product_card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #be2623, #ff4757);
  transform: scaleX(0);
  transition: transform 0.4s ease;
}

.product_card:hover::before {
  transform: scaleX(1);
}

.product_img_container {
  position: relative;
  overflow: hidden;
  height: 220px;
  background: #f8f9fa;
}

.product_img_container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.product_card:hover .product_img_container img {
  transform: scale(1.1);
}

.product_overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, rgba(190, 38, 35, 0.8), rgba(255, 71, 87, 0.8));
  opacity: 0;
  transition: opacity 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product_card:hover .product_overlay {
  opacity: 1;
}

.product_overlay i {
  color: white;
  font-size: 2.5rem;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.product_content {
  padding: 25px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.product_title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #0e3746;
  margin-bottom: 15px;
  line-height: 1.4;
  min-height: 2.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product_price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #be2623;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.product_price::before {
  content: 'R$';
  font-size: 1rem;
  font-weight: 500;
}

.product_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, #25d366, #128c7e);
  color: white;
  text-decoration: none;
  border-radius: 25px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.product_btn::before {
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

.product_btn:hover {
  color: white;
  text-decoration: none;
  transform: translateY(-2px);
}

.product_btn:hover::before {
  left: 0;
}

.whatsapp_icon {
  margin-right: 8px;
  font-size: 1.1rem;
}

.stock_badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: linear-gradient(45deg, #28a745, #20c997);
  color: white;
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  z-index: 2;
}

/* Responsividade */
@media (max-width: 768px) {
  .products_section {
    padding: 60px 0;
  }
  
  .products_section .heading_container h2 {
    font-size: 2rem;
  }
  
  .product_img_container {
    height: 180px;
  }
  
  .product_content {
    padding: 20px;
  }
  
  .product_title {
    font-size: 1.1rem;
  }
  
  .product_price {
    font-size: 1.3rem;
  }
  
  /* Centralização dos cards em mobile */
  .products_container .row {
    justify-content: center !important;
  }
  
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 300px;
    flex: 0 0 auto;
  }
}

@media (max-width: 576px) {
  .products_section .heading_container h2 {
    font-size: 1.8rem;
  }
  
  .product_img_container {
    height: 160px;
  }
  
  .product_btn {
    padding: 10px 25px;
    font-size: 0.9rem;
  }
  
  /* Centralização em telas muito pequenas */
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 280px;
    margin: 0 auto 20px auto;
  }
}
</style>

</div>

<?php 
$query = $pdo->query("SELECT * FROM produtos where estoque > 0 and valor_venda > 0 ORDER BY id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){ 
?>

<section class="products_section">
  <div class="container-fluid">
    <div class="heading_container heading_center">
      <h2>Nossos Produtos</h2>
      <p class="col-lg-8 px-0">
        Confira nossa seleção exclusiva de produtos de qualidade premium para cuidados masculinos. Oferecemos descontos especiais para compras em grande quantidade.
      </p>
    </div>
    
    <div class="products_container">
      <div class="row justify-content-center">
        <?php 
        for($i=0; $i < $total_reg; $i++){
          foreach ($res[$i] as $key => $value){}
          
          $id = $res[$i]['id'];
          $nome = $res[$i]['nome'];   
          $valor = $res[$i]['valor_venda'];
          $foto = $res[$i]['foto'];
          $descricao = $res[$i]['descricao'];
          $valorF = number_format($valor, 2, ',', '.');
        ?>
        
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 d-flex">
          <div class="product_card">
            <div class="product_img_container">
              <img src="sistema/painel/img/produtos/<?php echo $foto ?>" alt="<?php echo $nome ?>" title="<?php echo $descricao ?>">
              <div class="product_overlay">
                <i class="fa fa-shopping-cart"></i>
              </div>
              <div class="stock_badge">
                <i class="fa fa-check"></i> Em Estoque
              </div>
            </div>
            <div class="product_content">
              <h5 class="product_title"><?php echo $nome ?></h5>
              <div class="product_price"><?php echo $valorF ?></div>
              <a target="_blank" href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>&text=Olá, gostaria de saber mais informações sobre o produto <?php echo $nome ?>" class="product_btn">
                <i class="fa fa-whatsapp whatsapp_icon"></i>Comprar no WhatsApp
              </a>
            </div>
          </div>
        </div>
        
        <?php } ?>    
      </div>
    </div>
  </div>
</section>

<?php } ?>

  <!-- product section ends -->




 
   <?php require_once("rodape.php") ?>