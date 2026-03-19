<?php require_once("cabecalho.php") ?>


<style type="text/css">
.sub_page .hero_area {
  min-height: auto;
}

/* Estilos melhorados para a seção de serviços */
.services_section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 80px 0;
}

.services_section .heading_container {
  margin-bottom: 60px;
}

.services_section .heading_container h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0e3746;
  margin-bottom: 20px;
  position: relative;
}

.services_section .heading_container h2::after {
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

.services_section .heading_container p {
  font-size: 1.1rem;
  color: #6c757d;
  line-height: 1.6;
  margin-bottom: 0;
}

.services_container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
  display: flex;
  justify-content: center;
}

.services_container .row {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.service_card {
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

.service_card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.service_card::before {
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

.service_card:hover::before {
  transform: scaleX(1);
}

.service_img_container {
  position: relative;
  overflow: hidden;
  height: 220px;
  background: #f8f9fa;
}

.service_img_container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.service_card:hover .service_img_container img {
  transform: scale(1.1);
}

.service_overlay {
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

.service_card:hover .service_overlay {
  opacity: 1;
}

.service_overlay i {
  color: white;
  font-size: 2.5rem;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.service_content {
  padding: 25px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.service_title {
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

.service_price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #be2623;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.service_price::before {
  content: 'R$';
  font-size: 1rem;
  font-weight: 500;
}

.service_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, #be2623, #ff4757);
  color: white;
  text-decoration: none;
  border-radius: 25px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.service_btn::before {
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

.service_btn:hover {
  color: white !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.service_btn:hover::before {
  left: 0;
}

/* Estilos para botões em formulários e modais */
.btn_box .service_btn,
.btn-box2 .service_btn,
.modal-footer .service_btn,
.footer_form .service_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, #be2623, #ff4757);
  color: white;
  text-decoration: none;
  border-radius: 25px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  min-width: 150px;
  height: 48px;
  line-height: 24px;
  text-align: center;
  box-sizing: border-box;
}

.btn_box .service_btn::before,
.btn-box2 .service_btn::before,
.modal-footer .service_btn::before,
.footer_form .service_btn::before {
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

.btn_box .service_btn:hover,
.btn-box2 .service_btn:hover,
.modal-footer .service_btn:hover,
.footer_form .service_btn:hover {
  color: white !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.btn_box .service_btn:hover::before,
.btn-box2 .service_btn:hover::before,
.modal-footer .service_btn:hover::before,
.footer_form .service_btn:hover::before {
  left: 0;
}

/* Ajustes específicos para o botão no rodapé */
.footer_form .service_btn {
  width: 100%;
  margin-top: 10px;
  min-width: auto;
}

/* Garantir consistência visual em todos os contextos */
.service_btn {
  border-radius: 25px !important;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Ajustes para botões específicos */
.btn1,
.btn-box a,
.btn-box2 a {
  border-radius: 25px;
  min-height: 48px;
  padding: 12px 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 150px;
  box-sizing: border-box;
}

/* Sobrescrever estilos conflitantes do CSS principal */
.contact_section .form_container button.service_btn {
  border-radius: 25px !important;
  padding: 12px 30px !important;
  min-height: 48px !important;
  min-width: 150px !important;
  text-transform: none !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  background: linear-gradient(45deg, #be2623, #ff4757) !important;
  border: none !important;
}

.contact_section .form_container button.service_btn:hover {
  background: linear-gradient(45deg, #0e3746, #2c5aa0) !important;
  color: #fff !important;
  transform: translateY(-2px) !important;
}

.footer_section .footer_form button.service_btn {
  border-radius: 25px !important;
  padding: 12px 30px !important;
  min-height: 48px !important;
  background: linear-gradient(45deg, #be2623, #ff4757) !important;
  border: none !important;
  text-transform: none !important;
}

.footer_section .footer_form button.service_btn:hover {
  background: linear-gradient(45deg, #0e3746, #2c5aa0) !important;
  color: #fff !important;
  transform: translateY(-2px) !important;
}

/* Ajustes para botões em modais */
.modal-footer .service_btn {
  margin-right: 10px;
}

.category_tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-bottom: 40px;
}

.category_tag {
  padding: 8px 16px;
  background: rgba(190, 38, 35, 0.1);
  color: #be2623;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
  border: 1px solid rgba(190, 38, 35, 0.2);
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
  color: white !important;
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

/* Estilos modernos para o mapa */
.map_container {
  position: relative;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.4s ease;
  border: 1px solid rgba(190, 38, 35, 0.1);
  min-height: 400px;
}

.map_container:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.map_container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #be2623, #ff4757);
  z-index: 2;
}

.map_container iframe {
  width: 100%;
  height: 100%;
  min-height: 400px;
  border: none;
  border-radius: 0 0 20px 20px;
  transition: all 0.3s ease;
}

.map_container:hover iframe {
  filter: brightness(1.05) contrast(1.1);
}

/* Overlay com informações do local */
.map_overlay {
  position: absolute;
  bottom: 20px;
  left: 20px;
  right: 20px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  z-index: 3;
  transform: translateY(100%);
  opacity: 0;
  transition: all 0.4s ease;
}

.map_container:hover .map_overlay {
  transform: translateY(0);
  opacity: 1;
}

.map_overlay h5 {
  color: #0e3746;
  font-weight: 700;
  margin-bottom: 10px;
  font-size: 1.1rem;
}

.map_overlay p {
  color: #6c757d;
  margin-bottom: 8px;
  font-size: 0.9rem;
  line-height: 1.4;
}

.map_overlay i {
  color: #be2623;
  margin-right: 8px;
  width: 16px;
}

.map_directions_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, #be2623, #ff4757);
  color: white;
  text-decoration: none;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 600;
  transition: all 0.3s ease;
  margin-top: 10px;
  position: relative;
  overflow: hidden;
  min-width: 150px;
  height: 48px;
  line-height: 24px;
  text-align: center;
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.map_directions_btn i {
  color: white;
}

.map_directions_btn::before {
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

.map_directions_btn:hover {
  color: white !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.map_directions_btn:hover i {
  color: white;
}

.map_directions_btn:hover::before {
  left: 0;
}

/* Modern Testimonials Styles */
.modern-heading {
  text-align: center;
  margin-bottom: 60px;
}

.modern-heading h2 {
  position: relative;
  display: inline-block;
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c1810;
  margin-bottom: 15px;
}

.testimonial-icon {
  color: #be2623;
  margin-right: 15px;
  font-size: 0.8em;
}

.testimonial-underline {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background: linear-gradient(135deg, #be2623, #ff4757);
  border-radius: 2px;
}

.section-subtitle {
  color: #666;
  font-size: 1.1rem;
  margin: 0;
  font-style: italic;
}

.modern-testimonials {
  margin-top: 50px;
}

.testimonial-card {
  background: #fff;
  border-radius: 20px;
  padding: 30px;
  margin: 15px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  position: relative;
  transition: all 0.3s ease;
  border: 1px solid #f0f0f0;
  overflow: hidden;
}

.testimonial-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(135deg, #be2623, #ff4757);
}

.testimonial-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.quote-icon {
  position: absolute;
  top: 20px;
  right: 25px;
  color: #be2623;
  font-size: 2rem;
  opacity: 0.3;
}

.testimonial-content {
  margin-bottom: 25px;
}

.testimonial-text {
  font-size: 1.1rem;
  line-height: 1.6;
  color: #555;
  font-style: italic;
  margin: 0;
  position: relative;
  z-index: 1;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 15px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.author-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #be2623;
  box-shadow: 0 4px 10px rgba(190, 38, 35, 0.3);
}

.author-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.author-info {
  flex: 1;
}

.author-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c1810;
  margin: 0 0 5px 0;
}

.rating {
  display: flex;
  gap: 2px;
}

.rating i {
  color: #ffc107;
  font-size: 0.9rem;
}

.modern-btn-container {
  text-align: center;
  margin-top: 50px;
}

.modern-testimonial-btn {
  background: linear-gradient(135deg, #be2623, #ff4757);
  color: white;
  border: none;
  padding: 15px 30px;
  border-radius: 30px;
  font-size: 1.1rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(190, 38, 35, 0.3);
}

.modern-testimonial-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #ff4757, #be2623);
  transition: left 0.3s ease;
  z-index: -1;
}

.modern-testimonial-btn:hover::before {
  left: 0;
}

.modern-testimonial-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(190, 38, 35, 0.4);
  color: white;
  text-decoration: none;
}

.impact_wall {
  background: linear-gradient(130deg, #11151c 0%, #17202a 100%);
  padding: 70px 0 55px;
}

.impact_wall .heading_container h2 {
  color: #fff;
}

.impact_wall .heading_container p {
  color: rgba(255, 255, 255, 0.75);
}

.impact_grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  grid-auto-rows: 90px;
  gap: 10px;
}

.impact_card {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
}

.impact_card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.impact_card:hover img {
  transform: scale(1.08);
}

.impact_card::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(17, 21, 28, 0.15), rgba(17, 21, 28, 0.8));
}

.impact_label {
  position: absolute;
  left: 14px;
  bottom: 10px;
  z-index: 2;
  color: #fff;
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: 0.4px;
}

.w-4 { grid-column: span 4; }
.w-5 { grid-column: span 5; }
.w-6 { grid-column: span 6; }
.w-7 { grid-column: span 7; }
.w-8 { grid-column: span 8; }
.h-2 { grid-row: span 2; }
.h-3 { grid-row: span 3; }

.saas_cta_block {
  background: linear-gradient(135deg, #be2623 0%, #8f1f1d 100%);
  padding: 70px 0;
  position: relative;
  overflow: hidden;
}

.saas_cta_block::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.16), transparent 45%);
}

.saas_cta_block .cta_inner {
  position: relative;
  z-index: 1;
  text-align: center;
  color: #fff;
}

.saas_cta_block h3 {
  font-size: 2.4rem;
  font-weight: 700;
  margin-bottom: 14px;
}

.saas_cta_block p {
  font-size: 1.1rem;
  opacity: 0.92;
  margin-bottom: 28px;
}

.saas_cta_badges {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 28px;
}

.saas_cta_badges span {
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.24);
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 0.88rem;
}

/* Home nova: vibrante e focada em criacao de empresa */
body:not(.sub_page) .hero_area {
  height: 100vh;
  overflow: hidden;
}

body:not(.sub_page) .hero_area .hero_bg_box {
  display: block !important;
}

.vibrant_hero,
.proof_ticker,
.mega_gallery,
.image_reel,
.criar_empresa_vibrante,
.mini_showcase,
.resultado_premium,
.depo_vibrante,
.floating_create_btn,
.impact_wall,
.saas_cta_block {
  display: none !important;
}

.vibrant_hero {
  position: relative;
  padding: 72px 0 48px;
  background: linear-gradient(125deg, #0e1118 0%, #151b28 46%, #be2623 175%);
  overflow: hidden;
}

.vibrant_hero::before {
  content: '';
  position: absolute;
  width: 520px;
  height: 520px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 90, 65, 0.26), transparent 70%);
  top: -220px;
  right: -140px;
}

.vibrant_hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: linear-gradient(transparent 96%, rgba(255, 255, 255, 0.05) 97%), linear-gradient(90deg, transparent 96%, rgba(255, 255, 255, 0.04) 97%);
  background-size: 46px 46px;
  opacity: 0.25;
  pointer-events: none;
}

.hero_stage {
  position: relative;
  z-index: 2;
}

.hero_flag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.25);
  color: #fff;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 7px 14px;
  border-radius: 999px;
  margin-bottom: 16px;
}

.hero_title_v2 {
  color: #fff;
  font-size: clamp(2.2rem, 5.6vw, 4.8rem);
  line-height: 1.01;
  font-weight: 800;
  margin: 0 0 14px;
  max-width: 760px;
  letter-spacing: -0.5px;
}

.hero_title_v2 strong {
  color: #ff9d75;
}

.hero_desc_v2 {
  color: rgba(255, 255, 255, 0.84);
  max-width: 660px;
  font-size: 1.08rem;
  line-height: 1.6;
  margin-bottom: 22px;
}

.hero_actions_v2 {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.hero_actions_v2 .cta_main {
  background: linear-gradient(45deg, #ff5c33, #be2623);
  color: #fff;
  border: 0;
  border-radius: 999px;
  padding: 15px 28px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 26px rgba(190, 38, 35, 0.34);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  animation: heroCtaPulse 2.6s ease-in-out infinite;
}

.hero_actions_v2 .cta_main::after {
  content: '';
  position: absolute;
  top: 0;
  left: -120%;
  width: 55%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
  transform: skewX(-24deg);
}

.hero_actions_v2 .cta_secondary {
  background: transparent;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: 999px;
  padding: 14px 24px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.25s ease, background 0.25s ease;
}

.hero_actions_v2 .cta_whats {
  background: rgba(37, 211, 102, 0.18);
  color: #d3ffe4;
  border: 1px solid rgba(37, 211, 102, 0.45);
  border-radius: 999px;
  padding: 14px 20px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.25s ease, background 0.25s ease;
}

.hero_actions_v2 .cta_main:hover {
  color: #fff;
  text-decoration: none;
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(190, 38, 35, 0.42);
  animation-play-state: paused;
}

.hero_actions_v2 .cta_main:hover::after {
  left: 150%;
  transition: left 0.75s ease;
}

.hero_actions_v2 .cta_secondary:hover {
  color: #fff;
  text-decoration: none;
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-2px);
}

.hero_actions_v2 .cta_whats:hover {
  color: #e8fff1;
  text-decoration: none;
  background: rgba(37, 211, 102, 0.28);
  transform: translateY(-2px);
}

.hero_trust_line {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.hero_trust_line span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.16);
  color: rgba(255, 255, 255, 0.92);
  font-size: 0.8rem;
}

.hero_trust_line i {
  color: #ff9d75;
}

.hero_offer_box {
  margin-bottom: 18px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 14px;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  max-width: 700px;
}

.hero_offer_text strong {
  display: block;
  color: #fff;
  font-size: 1rem;
  line-height: 1.15;
}

.hero_offer_text span {
  display: block;
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.86rem;
  margin-top: 4px;
}

.cta_burst {
  background: #ff764f;
  color: #fff;
  border-radius: 999px;
  padding: 11px 17px;
  font-weight: 700;
  font-size: 0.86rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.cta_burst:hover {
  color: #fff;
  text-decoration: none;
  background: #ff5d32;
}

.hero_microcopy {
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.84rem;
  margin: 0 0 16px;
}

.hero_microcopy i {
  color: #ff9d75;
  margin-right: 5px;
}

@keyframes heroCtaPulse {
  0%,
  100% {
    box-shadow: 0 12px 26px rgba(190, 38, 35, 0.34);
  }
  50% {
    box-shadow: 0 16px 34px rgba(255, 92, 51, 0.45);
  }
}

.hero_kpis {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  max-width: 720px;
}

.hero_kpi {
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 12px;
  padding: 14px 12px;
  color: #fff;
}

.hero_kpi strong {
  display: block;
  font-size: 1.5rem;
  line-height: 1;
  margin-bottom: 6px;
}

.hero_kpi span {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.8);
}

.hero_panel {
  background: rgba(10, 14, 22, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 22px 40px rgba(0, 0, 0, 0.35);
}

.hero_panel_header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.hero_panel_header span {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.78rem;
}

.hero_panel_header strong {
  color: #fff;
  font-size: 0.86rem;
  letter-spacing: 0.3px;
}

.hero_visual_stack {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  grid-auto-rows: 82px;
  gap: 10px;
}

.hero_visual_stack .card_img {
  border-radius: 12px;
  overflow: hidden;
  min-height: 82px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.28);
}

.hero_visual_stack .card_img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero_visual_stack .card_img.large {
  grid-column: span 2;
  grid-row: span 2;
}

.hero_visual_stack .card_img.tall {
  grid-row: span 2;
}

.hero_panel_footer {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.hero_stat_pill {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: rgba(255, 255, 255, 0.92);
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 0.76rem;
}

.hero_stat_pill i {
  color: #ff9d75;
  margin-right: 4px;
}

.proof_ticker {
  background: #11151c;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 12px 0;
}

.proof_row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.proof_badge {
  color: #fff;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.14);
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 0.85rem;
}

.proof_badge i {
  color: #ff7350;
  margin-right: 6px;
}

.criar_empresa_vibrante {
  padding: 68px 0;
  background: #f4f6fb;
}

.criar_empresa_vibrante .head {
  max-width: 760px;
  margin: 0 auto 26px;
  text-align: center;
}

.criar_empresa_vibrante .head h2 {
  font-size: 2.35rem;
  font-weight: 800;
  color: #11151c;
  margin-bottom: 10px;
}

.criar_empresa_vibrante .head p {
  color: #5b6470;
  font-size: 1.03rem;
}

.flow_steps {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.flow_item {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e8ecf5;
  padding: 16px;
}

.flow_item b {
  width: 30px;
  height: 30px;
  border-radius: 999px;
  background: #be2623;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
}

.flow_item h4 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 6px;
  color: #11151c;
}

.flow_item p {
  margin: 0;
  color: #6a7380;
  font-size: 0.9rem;
  line-height: 1.45;
}

.flow_footer {
  margin-top: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  background: #11151c;
  color: #fff;
  border-radius: 12px;
  padding: 18px;
}

.flow_footer p {
  margin: 0;
  color: rgba(255, 255, 255, 0.78);
}

.flow_footer .service_btn {
  border-radius: 999px;
  white-space: nowrap;
}

.mini_showcase {
  padding: 58px 0 40px;
  background: #11151c;
}

.mini_showcase h3 {
  color: #fff;
  font-size: 1.8rem;
  margin-bottom: 18px;
}

.mini_cards {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.mini_card {
  background: #1a1f2a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  overflow: hidden;
}

.mini_card img {
  width: 100%;
  height: 140px;
  object-fit: cover;
}

.mini_card .txt {
  padding: 12px;
}

.mini_card h5 {
  color: #fff;
  font-size: 0.95rem;
  margin: 0 0 4px;
}

.mini_card p {
  margin: 0;
  color: #ff9d7c;
  font-size: 0.88rem;
}

.mini_showcase.light {
  background: #f4f6fb;
}

.mini_showcase.light h3 {
  color: #131923;
}

.mini_showcase.light .mini_card {
  background: #fff;
  border: 1px solid #e6eaf3;
}

.mini_showcase.light .mini_card h5 {
  color: #131923;
}

.mini_showcase.light .mini_card p {
  color: #be2623;
}

.depo_vibrante {
  padding: 62px 0 74px;
  background: #11151c;
}

.depo_vibrante .head {
  text-align: center;
  margin-bottom: 24px;
}

.depo_vibrante .head h3 {
  color: #fff;
  font-size: 2rem;
  margin-bottom: 8px;
}

.depo_vibrante .head p {
  color: rgba(255, 255, 255, 0.75);
}

.depo_grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.depo_card {
  background: #1a1f2a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 16px;
}

.depo_card .top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.depo_card img {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.32);
}

.depo_card h5 {
  margin: 0;
  color: #fff;
  font-size: 0.95rem;
}

.depo_card .stars {
  color: #ffc107;
  font-size: 0.8rem;
}

.depo_card p {
  margin: 0;
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.9rem;
  line-height: 1.45;
}

.mega_gallery {
  padding: 62px 0;
  background: #0f131b;
}

.mega_gallery .head {
  text-align: center;
  margin-bottom: 22px;
}

.mega_gallery .head h3 {
  color: #fff;
  font-size: 2rem;
  margin-bottom: 8px;
}

.mega_gallery .head p {
  color: rgba(255, 255, 255, 0.75);
  margin: 0;
}

.mega_grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  grid-auto-rows: 88px;
  gap: 10px;
}

.mega_tile {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.mega_tile img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}

.mega_tile:hover img {
  transform: scale(1.08);
}

.mega_tile::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(15, 19, 27, 0.1), rgba(15, 19, 27, 0.8));
}

.mega_tile span {
  position: absolute;
  left: 12px;
  bottom: 9px;
  z-index: 2;
  color: #fff;
  font-size: 0.85rem;
  font-weight: 600;
}

.c2 { grid-column: span 2; }
.c3 { grid-column: span 3; }
.c4 { grid-column: span 4; }
.c5 { grid-column: span 5; }
.c6 { grid-column: span 6; }
.r2 { grid-row: span 2; }
.r3 { grid-row: span 3; }

.image_reel {
  background: #101621;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
  padding: 14px 0;
}

.reel_track {
  display: flex;
  gap: 10px;
  width: max-content;
  animation: reelMove 30s linear infinite;
}

.reel_item {
  width: 150px;
  height: 96px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.12);
  flex: 0 0 auto;
}

.reel_item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

@keyframes reelMove {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

.resultado_premium {
  background: #f4f6fb;
  padding: 64px 0;
}

.resultado_head {
  text-align: center;
  margin-bottom: 24px;
}

.resultado_head h3 {
  font-size: 2rem;
  color: #11151c;
  margin-bottom: 8px;
}

.resultado_head p {
  margin: 0;
  color: #5f6773;
}

.resultado_grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr;
  gap: 12px;
}

.resultado_left {
  background: linear-gradient(135deg, #11151c 0%, #1b2533 100%);
  border-radius: 14px;
  padding: 24px;
  color: #fff;
  min-height: 280px;
}

.resultado_left h4 {
  font-size: 1.5rem;
  margin-bottom: 8px;
}

.resultado_left p {
  color: rgba(255, 255, 255, 0.78);
  margin-bottom: 16px;
}

.resultado_points {
  list-style: none;
  padding: 0;
  margin: 0;
}

.resultado_points li {
  margin-bottom: 8px;
  color: #fff;
  font-size: 0.92rem;
}

.resultado_points i {
  color: #ff8d6b;
  margin-right: 7px;
}

.resultado_card {
  background: #fff;
  border: 1px solid #e6ebf5;
  border-radius: 14px;
  padding: 14px;
}

.resultado_card img {
  width: 100%;
  height: 170px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 10px;
}

.resultado_card h5 {
  margin: 0 0 6px;
  color: #11151c;
  font-size: 1rem;
}

.resultado_card p {
  margin: 0;
  color: #6b7481;
  font-size: 0.9rem;
}

.floating_create_btn {
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 1200;
  background: linear-gradient(45deg, #ff5c33, #be2623);
  color: #fff;
  border-radius: 999px;
  padding: 12px 18px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  font-size: 0.92rem;
  font-weight: 700;
  box-shadow: 0 12px 24px rgba(190, 38, 35, 0.35);
}

.floating_create_btn:hover {
  color: #fff;
  text-decoration: none;
}

.btn-box .hero_alt_btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.6);
  margin-left: 10px;
}

.btn-box .hero_alt_btn::before {
  background: rgba(255, 255, 255, 0.18);
}

.criar_empresa_section {
  padding: 70px 0;
  background: #f4f6fb;
}

.criar_empresa_head {
  text-align: center;
  max-width: 760px;
  margin: 0 auto 35px;
}

.criar_empresa_head h2 {
  font-size: 2.4rem;
  font-weight: 700;
  color: #11151c;
  margin-bottom: 10px;
}

.criar_empresa_head p {
  color: #5f6773;
  font-size: 1.05rem;
}

.onboarding_grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 22px;
}

.onboarding_item {
  background: #fff;
  border: 1px solid #e8ebf2;
  border-radius: 12px;
  padding: 18px 16px;
}

.onboarding_number {
  display: inline-flex;
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #be2623;
  color: #fff;
  font-size: 0.85rem;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  margin-bottom: 10px;
}

.onboarding_item h4 {
  font-size: 1rem;
  color: #11151c;
  font-weight: 700;
  margin-bottom: 6px;
}

.onboarding_item p {
  margin: 0;
  font-size: 0.9rem;
  color: #697281;
  line-height: 1.45;
}

.onboarding_cta {
  background: linear-gradient(135deg, #11151c 0%, #1d2733 100%);
  border-radius: 14px;
  padding: 28px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  color: #fff;
}

.onboarding_cta h3 {
  margin: 0;
  font-size: 1.45rem;
  font-weight: 700;
}

.onboarding_cta p {
  margin: 6px 0 0;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.95rem;
}

.onboarding_cta .service_btn {
  white-space: nowrap;
  border: 0;
}

/* Responsive Design for Testimonials */
@media (max-width: 768px) {
  .modern-heading h2 {
    font-size: 2rem;
  }

  .impact_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 130px;
  }

  .w-4,
  .w-5,
  .w-6,
  .w-7,
  .w-8 {
    grid-column: span 1;
  }

  .h-2,
  .h-3 {
    grid-row: span 1;
  }

  .saas_cta_block h3 {
    font-size: 1.8rem;
  }

  .btn-box .hero_alt_btn {
    margin-left: 0;
    margin-top: 10px;
  }

  .onboarding_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .onboarding_cta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .testimonial-card {
    margin: 10px 5px;
    padding: 20px;
  }
  
  .testimonial-author {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .author-avatar {
    width: 50px;
    height: 50px;
  }

  .hero_title_v2 {
    font-size: 2rem;
  }

  .hero_kpis {
    grid-template-columns: 1fr;
  }

  .hero_visual_stack {
    grid-template-columns: 1fr;
  }

  .hero_visual_stack .card_img.large {
    grid-column: span 1;
  }

  .hero_actions_v2 {
    flex-direction: column;
    align-items: stretch;
  }

  .hero_actions_v2 .cta_main,
  .hero_actions_v2 .cta_secondary,
  .hero_actions_v2 .cta_whats {
    justify-content: center;
    width: 100%;
  }

  .hero_offer_box {
    flex-direction: column;
    align-items: flex-start;
  }

  .cta_burst {
    width: 100%;
    justify-content: center;
  }

  .flow_steps {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .flow_footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .mini_cards,
  .depo_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .mega_grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-auto-rows: 130px;
  }

  .c2,
  .c3,
  .c4,
  .c5,
  .c6 {
    grid-column: span 1;
  }

  .r2,
  .r3 {
    grid-row: span 1;
  }

  .resultado_grid {
    grid-template-columns: 1fr;
  }

  .resultado_left {
    min-height: auto;
  }
}

/* Responsividade */
@media (max-width: 768px) {
  .services_section, .products_section {
    padding: 60px 0;
  }
  
  .services_section .heading_container h2,
  .products_section .heading_container h2 {
    font-size: 2rem;
  }
  
  .service_img_container, .product_img_container {
    height: 180px;
  }
  
  .service_content, .product_content {
    padding: 20px;
  }
  
  .service_title, .product_title {
    font-size: 1.1rem;
  }
  
  .service_price, .product_price {
    font-size: 1.3rem;
  }
  
  /* Centralização dos cards em mobile */
  .services_container .row,
  .products_container .row {
    justify-content: center !important;
  }
  
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6,
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 300px;
    flex: 0 0 auto;
  }
  
  .map_container {
    min-height: 300px;
    margin-top: 30px;
  }
  
  .map_container iframe {
    min-height: 300px;
  }
  
  .map_overlay {
    bottom: 15px;
    left: 15px;
    right: 15px;
    padding: 15px;
  }
}

@media (max-width: 576px) {
  .criar_empresa_vibrante .head h2 {
    font-size: 1.8rem;
  }

  .flow_steps,
  .mini_cards,
  .depo_grid {
    grid-template-columns: 1fr;
  }

  .mega_grid {
    grid-template-columns: 1fr;
    grid-auto-rows: 150px;
  }

  .reel_item {
    width: 120px;
    height: 82px;
  }

  .floating_create_btn {
    left: 12px;
    right: 12px;
    bottom: 12px;
    text-align: center;
    display: block;
  }

  .criar_empresa_head h2 {
    font-size: 1.8rem;
  }

  .onboarding_grid {
    grid-template-columns: 1fr;
  }

  .services_section .heading_container h2,
  .products_section .heading_container h2 {
    font-size: 1.8rem;
  }
  
  .service_img_container, .product_img_container {
    height: 160px;
  }
  
  .service_btn, .product_btn {
    padding: 10px 25px;
    font-size: 0.9rem;
  }
  
  /* Centralização em telas muito pequenas */
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6,
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 280px;
    margin: 0 auto 20px auto;
  }
}
</style>

<?php if (false): ?>
<?php
$queryStats = $pdo->query("SELECT COUNT(*) AS total FROM servicos WHERE ativo = 'Sim'");
$totalServicosAtivos = (int) $queryStats->fetch(PDO::FETCH_ASSOC)['total'];

$queryStats = $pdo->query("SELECT COUNT(*) AS total FROM produtos WHERE estoque > 0 AND valor_venda > 0");
$totalProdutosAtivos = (int) $queryStats->fetch(PDO::FETCH_ASSOC)['total'];

$queryStats = $pdo->query("SELECT COUNT(*) AS total FROM clientes");
$totalClientes = (int) $queryStats->fetch(PDO::FETCH_ASSOC)['total'];
?>

<section class="vibrant_hero">
  <div class="container hero_stage">
    <span class="hero_flag"><i class="fa fa-bolt"></i> Plataforma SaaS para barbearias</span>

    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="hero_title_v2">Abra sua <strong>Nova Empresa</strong> hoje e transforme visitas em agenda cheia</h1>
        <p class="hero_desc_v2">Sua estrutura SaaS nasce pronta: subdominio proprio, painel administrativo, agendamento online e ambiente dedicado para operar com cara de marca grande.</p>

        <div class="hero_trust_line">
          <span><i class="fa fa-shield"></i> sem cartao de credito</span>
          <span><i class="fa fa-clock-o"></i> ativacao em poucos minutos</span>
          <span><i class="fa fa-check-circle"></i> subdominio proprio</span>
        </div>

        <div class="hero_actions_v2">
          <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="cta_main">
            <i class="fa fa-rocket"></i> Comecar Minha Empresa Agora
          </a>
          <a href="#como-criar-empresa" class="cta_secondary">
            <i class="fa fa-list"></i> Ver Passo a Passo
          </a>
          <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>&text=Ola,%20quero%20ajuda%20para%20criar%20minha%20nova%20empresa%20no%20SaaS" target="_blank" class="cta_whats">
            <i class="fa fa-whatsapp"></i> Falar com Time de Implantacao
          </a>
        </div>

        <div class="hero_offer_box">
          <div class="hero_offer_text">
            <strong>Ativacao imediata + acesso entregue na hora</strong>
            <span>Voce preenche os dados uma vez e o sistema cria banco, dominio e painel sem processo manual.</span>
          </div>
          <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="cta_burst">
            <i class="fa fa-plus-circle"></i> Criar em 2 Min
          </a>
        </div>

        <p class="hero_microcopy"><i class="fa fa-info-circle"></i>Ao finalizar, voce recebe URL, email e senha imediatamente, com opcao de envio direto no WhatsApp.</p>

        <div class="hero_kpis">
          <div class="hero_kpi">
            <strong><?php echo $totalServicosAtivos; ?>+</strong>
            <span>servicos prontos para vender</span>
          </div>
          <div class="hero_kpi">
            <strong><?php echo $totalProdutosAtivos; ?>+</strong>
            <span>produtos no catalogo</span>
          </div>
          <div class="hero_kpi">
            <strong><?php echo $totalClientes; ?>+</strong>
            <span>clientes ja cadastrados</span>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero_panel">
          <div class="hero_panel_header">
            <strong>Painel de Conversao</strong>
            <span>nova empresa</span>
          </div>

          <div class="hero_visual_stack">
            <div class="card_img large">
              <img src="sistema/painel/img/servicos/27-08-2025-21-01-21-completo.png" alt="Barbearia completa">
            </div>
            <div class="card_img tall">
              <img src="sistema/painel/img/servicos/27-08-2025-20-53-30-corte.png" alt="Corte moderno">
            </div>
            <div class="card_img">
              <img src="sistema/painel/img/produtos/27-08-2025-20-33-52-pomada.png" alt="Produto de venda">
            </div>
            <div class="card_img">
              <img src="sistema/painel/img/perfil/27-08-2025-21-16-36-gerente.png" alt="Gestao profissional">
            </div>
          </div>

          <div class="hero_panel_footer">
            <span class="hero_stat_pill"><i class="fa fa-fire"></i> foco em conversao</span>
            <span class="hero_stat_pill"><i class="fa fa-calendar"></i> agenda online ativa</span>
            <span class="hero_stat_pill"><i class="fa fa-lock"></i> ambiente dedicado</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="proof_ticker">
  <div class="container">
    <div class="proof_row">
      <span class="proof_badge"><i class="fa fa-check-circle"></i> Criacao automatica de banco tenant</span>
      <span class="proof_badge"><i class="fa fa-check-circle"></i> Subdominio proprio no cadastro</span>
      <span class="proof_badge"><i class="fa fa-check-circle"></i> Link e acesso entregues na hora</span>
      <span class="proof_badge"><i class="fa fa-check-circle"></i> Cadastro guiado para nova empresa</span>
    </div>
  </div>
</section>

<section class="mega_gallery">
  <div class="container">
    <div class="head">
      <h3>Galeria de impacto para sua marca</h3>
      <p>Mais imagem, mais desejo, mais agendamentos.</p>
    </div>

    <div class="mega_grid">
      <article class="mega_tile c6 r3">
        <img src="sistema/painel/img/servicos/27-08-2025-21-01-21-completo.png" alt="Visual premium">
        <span>Resultado premium</span>
      </article>
      <article class="mega_tile c3 r2">
        <img src="sistema/painel/img/servicos/27-08-2025-20-53-30-corte.png" alt="Corte moderno">
        <span>Corte moderno</span>
      </article>
      <article class="mega_tile c3 r2">
        <img src="sistema/painel/img/servicos/27-08-2025-20-53-41-barba.png" alt="Barba de respeito">
        <span>Barba de respeito</span>
      </article>
      <article class="mega_tile c4 r2">
        <img src="sistema/painel/img/servicos/27-08-2025-20-53-48-corte_barba.png" alt="Combo corte e barba">
        <span>Combo corte + barba</span>
      </article>
      <article class="mega_tile c4 r2">
        <img src="sistema/painel/img/produtos/27-08-2025-20-34-52-oleo_barba.png" alt="Produto para barba">
        <span>Produtos que aumentam ticket</span>
      </article>
      <article class="mega_tile c4 r2">
        <img src="sistema/painel/img/perfil/27-08-2025-21-16-36-gerente.png" alt="Equipe especializada">
        <span>Equipe especializada</span>
      </article>
    </div>
  </div>
</section>

<section class="image_reel">
  <div class="reel_track">
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-30-corte.png" alt="Corte"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-41-barba.png" alt="Barba"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-48-corte_barba.png" alt="Combo"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-21-02-14-tratamento_capilar.png" alt="Tratamento"></div>
    <div class="reel_item"><img src="sistema/painel/img/produtos/27-08-2025-20-33-52-pomada.png" alt="Pomada"></div>
    <div class="reel_item"><img src="sistema/painel/img/produtos/27-08-2025-20-39-25-gel.png" alt="Gel"></div>
    <div class="reel_item"><img src="sistema/painel/img/perfil/27-08-2025-21-15-48-carlos.png" alt="Profissional"></div>
    <div class="reel_item"><img src="sistema/painel/img/perfil/27-08-2025-21-16-08-maria.png" alt="Profissional"></div>

    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-30-corte.png" alt="Corte"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-41-barba.png" alt="Barba"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-20-53-48-corte_barba.png" alt="Combo"></div>
    <div class="reel_item"><img src="sistema/painel/img/servicos/27-08-2025-21-02-14-tratamento_capilar.png" alt="Tratamento"></div>
    <div class="reel_item"><img src="sistema/painel/img/produtos/27-08-2025-20-33-52-pomada.png" alt="Pomada"></div>
    <div class="reel_item"><img src="sistema/painel/img/produtos/27-08-2025-20-39-25-gel.png" alt="Gel"></div>
    <div class="reel_item"><img src="sistema/painel/img/perfil/27-08-2025-21-15-48-carlos.png" alt="Profissional"></div>
    <div class="reel_item"><img src="sistema/painel/img/perfil/27-08-2025-21-16-08-maria.png" alt="Profissional"></div>
  </div>
</section>

<section id="como-criar-empresa" class="criar_empresa_vibrante">
  <div class="container">
    <div class="head">
      <h2>Nova Empresa em 4 passos</h2>
      <p>Sem enrolacao: voce preenche os dados e a plataforma monta o ambiente completo automaticamente.</p>
    </div>

    <div class="flow_steps">
      <article class="flow_item">
        <b>1</b>
        <h4>Dados da empresa</h4>
        <p>Nome da empresa, responsavel, WhatsApp e email para liberar seu ambiente.</p>
      </article>
      <article class="flow_item">
        <b>2</b>
        <h4>Subdominio unico</h4>
        <p>Escolha seu endereco personalizado: <strong>suaempresa.superzap.fun</strong>.</p>
      </article>
      <article class="flow_item">
        <b>3</b>
        <h4>Provisionamento</h4>
        <p>Banco, tunnel e roteamento sao criados automaticamente no processo.</p>
      </article>
      <article class="flow_item">
        <b>4</b>
        <h4>Operacao imediata</h4>
        <p>URL, email e senha aparecem na tela para comecar no mesmo instante.</p>
      </article>
    </div>

    <div class="flow_footer">
      <div>
        <h3 style="margin: 0 0 4px; font-size: 1.35rem;">Abertura da nova empresa em menos de 2 minutos</h3>
        <p>Sem cartao de credito e sem configuracao manual.</p>
      </div>
      <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="service_btn">
        <i class="fa fa-rocket"></i> Criar Nova Empresa
      </a>
    </div>
  </div>
</section>

<section class="mini_showcase">
  <div class="container">
    <h3>Servicos com alto potencial de agenda</h3>
    <div class="mini_cards">
      <?php
      $query = $pdo->query("SELECT nome, valor, foto FROM servicos WHERE ativo = 'Sim' ORDER BY id DESC LIMIT 4");
      $res = $query->fetchAll(PDO::FETCH_ASSOC);
      foreach ($res as $item) {
        $nome = $item['nome'];
        $valor = number_format((float) $item['valor'], 2, ',', '.');
        $foto = trim((string) $item['foto']) !== '' ? $item['foto'] : 'sem-foto.jpg';
      ?>
      <article class="mini_card">
        <img src="sistema/painel/img/servicos/<?php echo $foto; ?>" alt="<?php echo $nome; ?>">
        <div class="txt">
          <h5><?php echo $nome; ?></h5>
          <p>R$ <?php echo $valor; ?></p>
        </div>
      </article>
      <?php } ?>
    </div>
  </div>
</section>

<section class="mini_showcase light">
  <div class="container">
    <h3>Produtos para aumentar ticket medio</h3>
    <div class="mini_cards">
      <?php
      $query = $pdo->query("SELECT nome, valor_venda, foto FROM produtos WHERE estoque > 0 AND valor_venda > 0 ORDER BY id DESC LIMIT 4");
      $res = $query->fetchAll(PDO::FETCH_ASSOC);
      foreach ($res as $item) {
        $nome = $item['nome'];
        $valor = number_format((float) $item['valor_venda'], 2, ',', '.');
        $foto = trim((string) $item['foto']) !== '' ? $item['foto'] : 'sem-foto.jpg';
      ?>
      <article class="mini_card">
        <img src="sistema/painel/img/produtos/<?php echo $foto; ?>" alt="<?php echo $nome; ?>">
        <div class="txt">
          <h5><?php echo $nome; ?></h5>
          <p>R$ <?php echo $valor; ?></p>
        </div>
      </article>
      <?php } ?>
    </div>
  </div>
</section>

<section class="resultado_premium">
  <div class="container">
    <div class="resultado_head">
      <h3>Pacote visual rico para converter mais</h3>
      <p>Uma home viva, com prova social, galeria forte e foco em abrir nova empresa.</p>
    </div>

    <div class="resultado_grid">
      <article class="resultado_left">
        <h4>O que essa pagina entrega</h4>
        <p>Estrutura desenhada para transformar visita em cadastro, com narrativa visual forte e CTA claro.</p>
        <ul class="resultado_points">
          <li><i class="fa fa-check-circle"></i>Hero com proposta direta de criacao de empresa</li>
          <li><i class="fa fa-check-circle"></i>Mural de imagens e carrossel visual continuo</li>
          <li><i class="fa fa-check-circle"></i>Fluxo em 4 passos para reduzir friccao</li>
          <li><i class="fa fa-check-circle"></i>Depoimentos em volume para fortalecer confianca</li>
          <li><i class="fa fa-check-circle"></i>CTA fixo para nao perder oportunidade de cadastro</li>
        </ul>
      </article>

      <article class="resultado_card">
        <img src="sistema/painel/img/servicos/27-08-2025-20-55-06-barba_bigode.png" alt="Barba e bigode">
        <h5>Imagem que vende servico</h5>
        <p>Cards de alto contraste para chamar clique e agendamento.</p>
      </article>

      <article class="resultado_card">
        <img src="sistema/painel/img/produtos/27-08-2025-20-35-54-condicionador.png" alt="Produto de venda">
        <h5>Ticket medio maior</h5>
        <p>Produtos destacados para aumentar faturamento por cliente.</p>
      </article>
    </div>
  </div>
</section>

<section class="depo_vibrante">
  <div class="container">
    <div class="head">
      <h3>Depoimentos que geram confianca</h3>
      <p>Mais credibilidade para converter novos clientes na sua pagina.</p>
    </div>

    <div class="depo_grid">
      <?php
      $query = $pdo->query("SELECT nome, texto, foto FROM comentarios WHERE ativo = 'Sim' ORDER BY id DESC LIMIT 30");
      $res = $query->fetchAll(PDO::FETCH_ASSOC);

      $fallbackDepoimentos = [
        ['nome' => 'Rafael Nunes', 'texto' => 'Depois da pagina nova, os agendamentos aumentaram e os clientes confiam mais na marca.', 'foto' => '27-08-2025-21-07-09-05.png'],
        ['nome' => 'Gabriel Lima', 'texto' => 'Ficou muito mais profissional. A criacao da empresa ficou clara e facilitou o fechamento.', 'foto' => '27-08-2025-21-07-17-06.png'],
        ['nome' => 'Felipe Costa', 'texto' => 'Visual forte, moderno e com cara de negocio serio. Excelente para converter mais.', 'foto' => '27-08-2025-21-07-36-07.png'],
        ['nome' => 'Diego Alves', 'texto' => 'Agora a home passa credibilidade de verdade. Ficou rica e muito mais vendedora.', 'foto' => '27-08-2025-21-08-59-08.png'],
        ['nome' => 'Lucas Prado', 'texto' => 'Os clientes sentiram a diferenca no mesmo dia. Muito mais profissional.', 'foto' => '27-08-2025-21-07-00-03.png'],
        ['nome' => 'Bruno Matos', 'texto' => 'A pagina ficou forte e objetiva. Hoje converte muito melhor.', 'foto' => '27-08-2025-21-05-12-01.png'],
      ];

      $depoimentosFinal = [];
      $fotosUsadas = [];

      foreach ($res as $item) {
        $foto = trim((string) $item['foto']);
        if ($foto === '') {
          continue;
        }

        $caminhoFoto = __DIR__ . '/sistema/painel/img/comentarios/' . $foto;
        if (!file_exists($caminhoFoto)) {
          continue;
        }

        if (in_array($foto, $fotosUsadas, true)) {
          continue;
        }

        $depoimentosFinal[] = [
          'nome' => $item['nome'],
          'texto' => $item['texto'],
          'foto' => $foto,
        ];
        $fotosUsadas[] = $foto;

        if (count($depoimentosFinal) >= 4) {
          break;
        }
      }

      if (count($depoimentosFinal) < 4) {
        foreach ($fallbackDepoimentos as $fb) {
          $foto = $fb['foto'];
          $caminhoFoto = __DIR__ . '/sistema/painel/img/comentarios/' . $foto;
          if (!file_exists($caminhoFoto)) {
            continue;
          }

          if (in_array($foto, $fotosUsadas, true)) {
            continue;
          }

          $depoimentosFinal[] = $fb;
          $fotosUsadas[] = $foto;

          if (count($depoimentosFinal) >= 4) {
            break;
          }
        }
      }

      foreach ($depoimentosFinal as $item) {
        $nome = $item['nome'];
        $texto = $item['texto'];
        $foto = $item['foto'];
      ?>
      <article class="depo_card">
        <div class="top">
          <img src="sistema/painel/img/comentarios/<?php echo $foto; ?>" alt="<?php echo $nome; ?>">
          <div>
            <h5><?php echo $nome; ?></h5>
            <div class="stars"><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i></div>
          </div>
        </div>
        <p>"<?php echo $texto; ?>"</p>
      </article>
      <?php } ?>
    </div>
  </div>
</section>

<a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="floating_create_btn">
  <i class="fa fa-plus-circle"></i> Criar Nova Empresa
</a>

<?php endif; ?>

<?php 
$query = $pdo->query("SELECT * FROM textos_index ORDER BY id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
 ?>
    <!-- slider section -->
    <section class="slider_section ">
      <div id="customCarousel1" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">

<?php 
for($i=0; $i < $total_reg; $i++){
  foreach ($res[$i] as $key => $value){}
  $id = $res[$i]['id'];
  $titulo = $res[$i]['titulo'];
  $descricao = $res[$i]['descricao'];

  if($i == 0){
    $ativo = 'active';
  }else{
    $ativo = '';
  }
 ?>

          <div class="carousel-item <?php echo $ativo ?>">
            <div class="container ">
              <div class="row">
                <div class="col-md-6 ">
                  <div class="detail-box">
                    <h1>
                     <?php echo $titulo ?>
                    </h1>
                    <p>
                     <?php echo $descricao ?>
                    </p>
                    <div class="btn-box">
                      <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="service_btn">
                        <i class="fa fa-rocket" aria-hidden="true"></i>
                        Criar Nova Empresa
                      </a>
                      <a href="#como-criar" class="service_btn hero_alt_btn">
                        <i class="fa fa-list-ol" aria-hidden="true"></i>
                        Ver 4 Passos
                      </a>
                      <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>&text=Ola,%20quero%20ajuda%20para%20criar%20minha%20nova%20empresa" target="_blank" class="service_btn hero_alt_btn">
                        <i class="fa fa-whatsapp" aria-hidden="true"></i>
                        Falar com Especialista
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
<?php 
}
 ?>

          
        </div>
        <div class="container">
          <ol class="carousel-indicators custom-indicators">
            <?php 
            for($j=0; $j < $total_reg; $j++){
              $classe_ativa = '';
              if($j == 0){
                $classe_ativa = 'class="active"';
              }
              echo '<li data-target="#customCarousel1" data-slide-to="'.$j.'" '.$classe_ativa.'></li>';
            }
            ?>
          </ol>
        </div>
      </div>
    </section>
    <!-- end slider section -->

  <?php } ?>

  </div>

  <section id="como-criar" class="criar_empresa_section">
    <div class="container">
      <div class="criar_empresa_head">
        <h2>Criacao de Nova Empresa em 4 Passos</h2>
        <p>Cadastre sua barbearia, escolha o subdominio e o sistema sobe automaticamente com banco, painel e acesso imediato.</p>
      </div>

      <div class="onboarding_grid">
        <div class="onboarding_item">
          <span class="onboarding_number">1</span>
          <h4>Preencha os dados</h4>
          <p>Informe nome da barbearia, responsavel, WhatsApp, email e senha.</p>
        </div>
        <div class="onboarding_item">
          <span class="onboarding_number">2</span>
          <h4>Defina seu subdominio</h4>
          <p>Voce escolhe o endereco: <strong>minhaempresa.superzap.fun</strong>.</p>
        </div>
        <div class="onboarding_item">
          <span class="onboarding_number">3</span>
          <h4>Ambiente criado</h4>
          <p>Banco tenant, tunnel e dominio sao configurados automaticamente.</p>
        </div>
        <div class="onboarding_item">
          <span class="onboarding_number">4</span>
          <h4>Comece a vender</h4>
          <p>Receba URL, login e senha na hora para iniciar atendimento.</p>
        </div>
      </div>

      <div class="onboarding_cta">
        <div>
          <h3>Pronto para criar sua nova empresa agora?</h3>
          <p>Leva menos de 2 minutos. Sem cartao e com ativacao imediata.</p>
        </div>
        <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="service_btn">
          <i class="fa fa-plus-circle"></i> Criar Nova Empresa
        </a>
      </div>
    </div>
  </section>

  <section class="impact_wall" style="display:none !important;">
    <div class="container-fluid">
      <div class="heading_container heading_center" style="margin-bottom: 28px;">
        <h2>Visual da Barbearia que Conquista</h2>
        <p class="col-lg-8 px-0">Ambiente, estilo e resultado real. Sua marca precisa dessa presença para vender mais.</p>
      </div>

      <div class="impact_grid">
        <div class="impact_card w-7 h-3">
          <img src="sistema/painel/img/servicos/27-08-2025-21-01-21-completo.png" alt="Combo completo">
          <span class="impact_label">Combo Premium</span>
        </div>

        <div class="impact_card w-5 h-2">
          <img src="sistema/painel/img/servicos/27-08-2025-20-53-30-corte.png" alt="Corte">
          <span class="impact_label">Corte Atual</span>
        </div>

        <div class="impact_card w-5 h-2">
          <img src="sistema/painel/img/servicos/27-08-2025-20-53-48-corte_barba.png" alt="Corte e barba">
          <span class="impact_label">Corte + Barba</span>
        </div>

        <div class="impact_card w-4 h-2">
          <img src="sistema/painel/img/produtos/27-08-2025-20-33-52-pomada.png" alt="Pomada modeladora">
          <span class="impact_label">Produtos de Giro</span>
        </div>

        <div class="impact_card w-4 h-2">
          <img src="sistema/painel/img/servicos/27-08-2025-20-55-06-barba_bigode.png" alt="Barba e bigode">
          <span class="impact_label">Barba de Impacto</span>
        </div>

        <div class="impact_card w-4 h-2">
          <img src="sistema/painel/img/servicos/27-08-2025-21-02-14-tratamento_capilar.png" alt="Tratamento capilar">
          <span class="impact_label">Tratamento Capilar</span>
        </div>

        <div class="impact_card w-6 h-2">
          <img src="sistema/painel/img/perfil/27-08-2025-21-15-48-carlos.png" alt="Profissional da equipe">
          <span class="impact_label">Equipe Especializada</span>
        </div>

        <div class="impact_card w-6 h-2">
          <img src="sistema/painel/img/perfil/27-08-2025-21-16-08-maria.png" alt="Profissional da equipe">
          <span class="impact_label">Atendimento Humanizado</span>
        </div>
      </div>
    </div>
  </section>

  <!-- services section -->
  <section class="services_section">
    <div class="container-fluid">
      <div class="heading_container heading_center">
        <h2>Nossos Serviços</h2>
        <p class="col-lg-8 px-0">
          <?php 
          $query = $pdo->query("SELECT * FROM cat_servicos ORDER BY id asc");
          $res = $query->fetchAll(PDO::FETCH_ASSOC);
          $total_reg = @count($res);
          if($total_reg > 0){ 
          ?>
          <div class="category_tags">
          <?php
          for($i=0; $i < $total_reg; $i++){
            foreach ($res[$i] as $key => $value){}
            $id = $res[$i]['id'];
            $nome = $res[$i]['nome'];
            echo '<span class="category_tag">' . $nome . '</span>';
          }
          ?>
          </div>
          <?php
          }
          
          $query = $pdo->query("SELECT * FROM servicos where ativo = 'Sim' ORDER BY id asc");
          $res = $query->fetchAll(PDO::FETCH_ASSOC);
          $total_reg = @count($res);
          if($total_reg > 0){ 
          ?>
          Descubra nossos serviços profissionais de barbearia com qualidade excepcional e atendimento personalizado.
        </p>
      </div>
      
      <div class="services_container">
        <div class="row justify-content-center">
          <?php 
          for($i=0; $i < $total_reg; $i++){
            foreach ($res[$i] as $key => $value){}
            
            $id = $res[$i]['id'];
            $nome = $res[$i]['nome'];   
            $valor = $res[$i]['valor'];
            $foto = $res[$i]['foto'];
            $valorF = number_format($valor, 2, ',', '.');
          ?>
          
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4 d-flex">
            <div class="service_card">
              <div class="service_img_container">
                <img src="sistema/painel/img/servicos/<?php echo $foto ?>" alt="<?php echo $nome ?>">
                <div class="service_overlay">
                  <i class="fa fa-cut"></i>
                </div>
              </div>
              <div class="service_content">
                <h5 class="service_title"><?php echo $nome ?></h5>
                <div class="service_price"><?php echo $valorF ?></div>
                <a href="agendamentos" class="service_btn">
                  <i class="fa fa-calendar"></i> Agendar Serviço
                </a>
              </div>
            </div>
          </div>
          
          <?php } ?>    
        </div>
      </div>
      
      <?php } ?>
    </div>
  </section>
  <!-- services section ends -->

  <!-- about section -->
  <section class="about_section ">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6 px-0">
          <div class="img-box ">
            <?php
            $imagemSobreArquivo = trim((string)$imagem_sobre) !== '' ? $imagem_sobre : 'foto_barbearia.jpg';
            if(!file_exists(__DIR__ . '/images/' . $imagemSobreArquivo)){
              $imagemSobreArquivo = 'foto_barbearia.jpg';
            }
            ?>
            <img src="images/<?php echo $imagemSobreArquivo ?>" class="box_img" alt="about img">
          </div>
        </div>
        <div class="col-md-5">
          <div class="detail-box ">
            <div class="heading_container">
              <h2 class="">
                Sobre Nós
              </h2>
            </div>
            <p class="detail_p_mt">
              <?php echo $texto_sobre ?>
            </p>
            <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" class="service_btn">
              <i class="fa fa-whatsapp"></i> Mais Informações
            </a>
            <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="service_btn" style="margin-left: 8px;">
              <i class="fa fa-rocket"></i> Criar Nova Empresa
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- about section ends -->

  <!-- products section -->
  <?php 
  $query = $pdo->query("SELECT * FROM produtos where estoque > 0 and valor_venda > 0 ORDER BY id desc limit 8");
  $res = $query->fetchAll(PDO::FETCH_ASSOC);
  $total_reg = @count($res);
  if($total_reg > 0){ 
  ?>

  <section class="products_section">
    <div class="container-fluid">
      <div class="heading_container heading_center">
        <h2>Nossos Produtos</h2>
        <p class="col-lg-8 px-0">
          Confira nossa seleção exclusiva de produtos de qualidade premium para cuidados masculinos.
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
      
      <div class="btn-box" style="text-align: center; margin-top: 40px;">
        <a href="produtos" style="display: inline-block; padding: 15px 40px; background: linear-gradient(45deg, #be2623, #ff4757); color: white; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease;">
          Ver mais Produtos
        </a>
      </div>
    </div>
  </section>

  <?php } ?>
  <!-- products section ends -->

  <section class="saas_cta_block" style="display:none !important;">
    <div class="container">
      <div class="cta_inner">
        <h3>Quer uma Barbearia Lotada Sem Complicar?</h3>
        <p>Crie seu sistema agora e tenha agendamento online, controle de clientes e painel completo em poucos minutos.</p>

        <div class="saas_cta_badges">
          <span><i class="fa fa-check"></i> Subdominio proprio</span>
          <span><i class="fa fa-check"></i> Trial automatico</span>
          <span><i class="fa fa-check"></i> Sem cartao</span>
        </div>

        <a href="#" data-toggle="modal" data-target="#modalCadastroSaas" class="service_btn" style="font-size: 1.05rem; padding: 14px 34px;">
          <i class="fa fa-rocket"></i> Criar Minha Barbearia Agora
        </a>
      </div>
    </div>
  </section>

  <!-- contact section -->
  <section class="contact_section layout_padding">
    <div class="container">
      <div class="heading_container">
        <h2>
          Contate-nos
        </h2>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form_container">
            <form id="form-email">
              <div>
                <input type="text" name="nome" placeholder="Seu Nome" required/>
              </div>
              <div>
                <input type="text" name="telefone" id="telefone" placeholder="Seu Telefone" required />
              </div>
              <div>
                <input type="email" name="email" placeholder="Seu Email" required />
              </div>
              <div>
                <input type="text" name="mensagem" class="message-box" placeholder="Mensagem" required />
              </div>
              <div class="btn_box">
                <button class="service_btn" type="submit">
                  <i class="fa fa-paper-plane"></i> Enviar
                </button>
              </div>
            </form>

            <br><div id="mensagem"></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="map_container">
           <?php echo $mapa ?>
           <div class="map_overlay">
             <h5><i class="fa fa-map-marker"></i>Nossa Localização</h5>
             <p><i class="fa fa-home"></i><?php echo $endereco_sistema ?></p>
             <p><i class="fa fa-phone"></i><?php echo $telefone_fixo_sistema ?></p>
             <p><i class="fa fa-clock-o"></i>Seg-Sex: 8h às 18h | Sáb: 8h às 16h</p>
             <a href="https://www.google.com/maps/dir//<?php echo urlencode($endereco_sistema) ?>" target="_blank" class="map_directions_btn">
               <i class="fa fa-map-marker"></i> Como Chegar
             </a>
           </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- end contact section -->

  <!-- client section -->
<?php 
$depoimentosPerfis = [
  ['nome' => 'Carlos Mendes', 'texto' => 'Equipe pontual, atendimento forte e visual impecavel. Ficou top!', 'foto' => '27-08-2025-21-15-48-carlos.png', 'origem' => 'perfil'],
  ['nome' => 'Alex Rocha', 'texto' => 'A pagina transmite profissionalismo e os clientes chegam mais decididos.', 'foto' => '27-08-2025-21-16-36-gerente.png', 'origem' => 'perfil'],
  ['nome' => 'Rafael Gomes', 'texto' => 'Layout limpo e vendedor. Melhorou minha conversao em poucos dias.', 'foto' => '27-08-2025-21-13-25-f3.png', 'origem' => 'perfil'],
  ['nome' => 'Thiago Freitas', 'texto' => 'Agora ficou com cara de marca grande. Excelente para fechar novos clientes.', 'foto' => '27-08-2025-21-07-00-03.png', 'origem' => 'comentarios'],
];

$depoimentosCards = [];
foreach($depoimentosPerfis as $item){
  $origemFoto = isset($item['origem']) ? $item['origem'] : 'perfil';
  $caminhoPerfil = __DIR__ . '/sistema/painel/img/' . $origemFoto . '/' . $item['foto'];
  if(!file_exists($caminhoPerfil)){
    continue;
  }

  $depoimentosCards[] = [
    'nome' => $item['nome'],
    'texto' => $item['texto'],
    'foto' => $item['foto'],
    'origem' => $origemFoto
  ];
}

$total_reg = count($depoimentosCards);
if($total_reg > 0){ 
 ?>
  <section class="client_section layout_padding-bottom">
    <div class="container">
      <div class="heading_container modern-heading">
        <h2>
          <span class="testimonial-icon"><i class="fa fa-quote-left"></i></span>
          Depoimento dos nossos Clientes
          <span class="testimonial-underline"></span>
        </h2>
        <p class="section-subtitle">Veja o que nossos clientes dizem sobre nossos serviços</p>
      </div>
      <div class="client_container modern-testimonials">
        <div class="carousel-wrap">
          <div class="owl-carousel client_owl-carousel">

            <?php 
            foreach($depoimentosCards as $item){
          $nome = $item['nome'];
          $texto = $item['texto'];
          $foto = $item['foto'];
          $pastaFoto = $item['origem'] === 'perfil' ? 'perfil' : 'comentarios';
              ?>

            <div class="item">
              <div class="testimonial-card">
                <div class="quote-icon">
                  <i class="fa fa-quote-right"></i>
                </div>
                <div class="testimonial-content">
                  <p class="testimonial-text">
                    "<?php echo $texto ?>"
                  </p>
                </div>
                <div class="testimonial-author">
                  <div class="author-avatar">
                    <img src="sistema/painel/img/<?php echo $pastaFoto ?>/<?php echo $foto ?>" alt="<?php echo $nome ?>">
                  </div>
                  <div class="author-info">
                    <h5 class="author-name"><?php echo $nome ?></h5>
                    <div class="rating">
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>


<?php } ?>

          </div>
        </div>
      </div>
    </div>

     <div class="btn-box2 modern-btn-container">
        <a href="" data-toggle="modal" data-target="#modalComentario" class="service_btn modern-testimonial-btn">
         <i class="fa fa-comment"></i> Inserir Depoimento
        </a>
      </div>

  </section>

<?php } ?>

  <!-- end client section -->

  <?php require_once("rodape.php") ?>

  <div class="modal fade" id="modalCadastroSaas" tabindex="-1" role="dialog" aria-labelledby="modalCadastroSaasLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="background: linear-gradient(45deg, #be2623, #ff4757); color: #fff;">
          <h5 class="modal-title" id="modalCadastroSaasLabel">Criar Nova Empresa SaaS</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 1;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form method="post" id="form-cadastro-saas">
          <div class="modal-body">
            <input class="form-control" type="text" name="empresa" id="saas-empresa" placeholder="Nome da Nova Empresa" required>
            <br>
            <input class="form-control" type="text" name="responsavel" id="saas-responsavel" placeholder="Seu Nome" required>
            <br>
            <input class="form-control" type="tel" name="telefone" id="saas-telefone" placeholder="WhatsApp (DDD + numero)" required>
            <br>
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
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            <button type="submit" class="service_btn">Criar Nova Empresa</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Depoimentos -->
  <div class="modal fade" id="modalComentario" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Inserir Depoimento
                   </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <form id="form">
      <div class="modal-body">

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label for="exampleInputEmail1">Nome</label>
                <input type="text" class="form-control" id="nome_cliente" name="nome" placeholder="Nome" required>    
              </div>  
            </div>
            <div class="col-md-12">

              <div class="form-group">
                <label for="exampleInputEmail1">Texto <small>(Até 500 Caracteres)</small></label>
                <textarea maxlength="500" class="form-control" id="texto_cliente" name="texto" placeholder="Texto Comentário" required> </textarea>   
              </div>  
            </div>
          </div>

            <div class="row">
              <div class="col-md-8">            
                <div class="form-group"> 
                  <label>Foto</label> 
                  <input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
                </div>            
              </div>
              <div class="col-md-4">
                <div id="divImg">
                  <img src="sistema/painel/img/comentarios/sem-foto.jpg"  width="80px" id="target">                  
                </div>
              </div>

            </div>

            <input type="hidden" name="id" id="id">
             <input type="hidden" name="cliente" value="1">

          <br>
          <small><div id="mensagem-comentario" align="center"></div></small>
        </div>

        <div class="modal-footer">      
          <button type="submit" class="service_btn"><i class="fa fa-plus"></i> Inserir</button>
        </div>
      </form>

      </div>
    </div>
  </div>

<script type="text/javascript">
  
$("#form-email").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'ajax/enviar-email.php',
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Enviado com Sucesso") {
               $('#mensagem').addClass('text-success')
                $('#mensagem').text(mensagem)

            } else {

                $('#mensagem').addClass('text-danger')
                $('#mensagem').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

</script>

<script type="text/javascript">
  function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#foto").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>

<script type="text/javascript">
  
$("#form").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);


    $.ajax({
        url: 'sistema/painel/paginas/comentarios/salvar.php',
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-comentario').text('');
            $('#mensagem-comentario').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {
            
            $('#mensagem-comentario').addClass('text-success')
                $('#mensagem-comentario').text('Comentário Enviado para Aprovação!')
                 $('#nome_cliente').val('');
                  $('#texto_cliente').val('');

            } else {

                $('#mensagem-comentario').addClass('text-danger')
                $('#mensagem-comentario').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

</script>

<script type="text/javascript">
  $("#form-cadastro-saas").submit(function (event) {
    event.preventDefault();
    var formData = new FormData(this);

    $('#mensagem-cadastro-saas').removeClass();
    $('#mensagem-cadastro-saas').addClass('text-info');
    $('#mensagem-cadastro-saas').html('Criando sua conta... aguarde alguns segundos.');

    $.ajax({
      url: "sistema/saas/cadastro_cliente.php",
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
          var html = 'Conta criada com sucesso!<br>';
          html += 'URL: <a href="' + dados.dominio + '" target="_blank">' + dados.dominio + '</a><br>';
          html += 'Email: ' + dados.email + '<br>';
          html += 'Senha: ' + dados.senha;

          if (dados.whatsapp_link) {
            html += '<br><br><a class="btn btn-success btn-sm" target="_blank" href="' + dados.whatsapp_link + '">';
            html += '<i class="fa fa-whatsapp"></i> Enviar no WhatsApp</a>';
          }

          $('#mensagem-cadastro-saas').addClass('text-success');
          $('#mensagem-cadastro-saas').html(html);

          $('#saas-senha').val('');
          $('#saas-confirmar-senha').val('');
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
