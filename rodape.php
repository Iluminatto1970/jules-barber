<?php require_once("sistema/conexao.php") ?>
<!-- footer section -->
  <footer class="footer_section">
    <div class="container">
      <div class="footer_content ">
        <div class="row ">
          <div class="col-md-5 col-lg-5 footer-col">
            <div class="footer_detail">
              <a href="index.php">
                <h4>
                  <?php echo $nome_sistema ?>
                </h4>
              </a>
              <p>
                <?php echo $texto_rodape ?>
              </p>
            </div>
          </div>
          <div class="col-md-7 col-lg-4 ">
            <h4>
              Contatos
            </h4>
            <div class="contact_nav footer-col">
              <a href="">
                <i class="fa fa-map-marker" aria-hidden="true"></i>
                <span>
                  <?php echo $endereco_sistema ?>
                </span>
              </a>
              <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>">
                <i class="fa fa-phone" aria-hidden="true"></i>
                <span>
                  Whatsapp : <?php echo $whatsapp_sistema ?>
                </span>
              </a>
              <a href="">
                <i class="fa fa-envelope" aria-hidden="true"></i>
                <span>
                  Email : <?php echo $email_sistema ?>
                </span>
              </a>
            </div>
          </div>
          <div class="col-lg-3">
            <div class="footer_form footer-col">
              <h4>
                CADASTRE-SE
              </h4>
              <form id="form_cadastro">
                <input type="text" name="telefone" id="telefone_rodape" placeholder="Seu Telefone DDD + número" />
                <input type="text" name="nome" placeholder="Seu Nome" />
                <button type="submit" class="service_btn" style="width: 100%; min-height: 48px; border-radius: 25px; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;">
                  <i class="fa fa-user-plus"></i> Cadastrar
                </button>
              </form>
              <br><small><div id="mensagem-rodape"></div></small>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </footer>
  <!-- footer section -->

  <!-- jQery -->
  <script src="js/jquery-3.4.1.min.js"></script>
  <!-- popper js -->
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <!-- bootstrap js -->
  <script src="js/bootstrap.js"></script>
  <!-- owl slider -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
  <!-- custom js -->
  <script src="js/custom.js"></script>
  <!-- Google Map API removido - usando iframe incorporado -->

    <!-- Mascaras JS -->
<script type="text/javascript" src="sistema/painel/js/mascaras.js"></script>

<!-- Ajax para funcionar Mascaras JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script> 

<!-- Botão Voltar ao Topo -->
<button id="backToTop" class="back-to-top" title="Voltar ao topo">
  <i class="fa fa-chevron-up"></i>
</button>

<style>
.back-to-top {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 50px;
  height: 50px;
  background-color: #be2623;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  font-size: 18px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.3);
  transition: all 0.3s ease;
  opacity: 0;
  visibility: hidden;
  z-index: 1000;
}

.back-to-top.show {
  opacity: 1;
  visibility: visible;
}

.back-to-top:hover {
  background-color: #161825;
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.4);
}

@media (max-width: 768px) {
  .back-to-top {
    bottom: 20px;
    right: 20px;
    width: 45px;
    height: 45px;
    font-size: 16px;
  }
}
</style>

<script>
// Mostrar/ocultar botão voltar ao topo
$(window).scroll(function() {
  if ($(this).scrollTop() > 300) {
    $('#backToTop').addClass('show');
  } else {
    $('#backToTop').removeClass('show');
  }
});

// Ação do botão voltar ao topo
$('#backToTop').click(function() {
  $('html, body').animate({scrollTop: 0}, 600);
  return false;
});
</script>

</body>

</html>


<script type="text/javascript">
  
$("#form_cadastro").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'ajax/cadastrar.php',
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-rodape').text('');
            $('#mensagem-rodape').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {
               //$('#mensagem-rodape').addClass('text-success')
                $('#mensagem-rodape').text(mensagem)

            } else {

                //$('#mensagem-rodape').addClass('text-danger')
                $('#mensagem-rodape').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});


</script>