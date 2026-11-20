<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang liên hệ /contact - Contact::index(). Form liên hệ gửi AJAX bằng js/site.js tới /contact/send.
?>

<!-- Home -->
<div class="home">
	<div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/contact_background.jpg'); ?>"></div>
	<div class="home_content">
		<div class="home_title">contact</div>
	</div>
</div>

<!-- Contact -->
<div class="contact_form_section page_body">
	<div class="container">
		<div class="row">
			<div class="col">
				<div class="contact_form_container">
					<div class="contact_title text-center">get in touch</div>

					<div id="contact_msg" class="alert" style="display:none"></div>

					<?php echo form_open('contact/send', ['id' => 'contact_form', 'class' => 'contact_form text-center']); ?>
						<input type="text" name="name" id="contact_form_name" class="contact_form_name input_field" placeholder="Name" required="required" maxlength="255">
						<input type="email" name="email" id="contact_form_email" class="contact_form_email input_field" placeholder="E-mail" required="required" maxlength="255">
						<textarea name="message" id="contact_form_message" class="text_field contact_form_message" rows="4" placeholder="Message" required="required"></textarea>
						<button type="submit" id="form_submit_button" class="form_submit_button button trans_200">send message<span></span><span></span><span></span></button>
					<?php echo form_close(); ?>
				</div>
			</div>
		</div>
	</div>
</div>