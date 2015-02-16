<?php
	class login {
		public function __construct ($cms) {
			$this->cms = $cms;
		}

		public function view () {
		?>
			<div class="login">
				<h1>Login</h1>
		<?php
			if ($this->cms->login->msg != "") {
				echo ('<p class="msg">'.$this->cms->login->msg.'</p>');
			}
		?>
				<form method="post" action="index.html">
				<p><input type="text" name="user" value="" placeholder="Username or Email"></p>
				<p><input type="password" name="pass" value="" placeholder="Password"></p>
				<p class="submit"><input type="submit" name="commit" value="Login"></p>
				</form>
			</div>
		<?php
		}
	}
?>
