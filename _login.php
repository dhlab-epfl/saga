<?php

if (isset($_REQUEST['register'])) {
	if (!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
  		$data['msg_errors'][] = "E-mail invalide";
	} else if(db_count(db_s('users', array('email' => $_REQUEST['email'])))) {
		$data['msg_errors'][] = "Un compte existe déjà pour cette adresse email.";
	} else {
		$user = [];
		$user['email'] = $_REQUEST['email'];
		$pass = chr(rand(65,90)).chr(rand(65,90)).chr(rand(65,90)).rand(100,999);
		$user['password_hash'] = sha1($pass.$key);
		$user['created_at'] = date('Y-m-d H:i:s');
		db_i('users', $user);


		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->Host = "mail.yabrab.ch";
		$mail->SMTPAuth = true;
		$mail->Username = "sendmail_simnar@dffy.ch";
		$mail->Password = "sFil29%1";
		$mail->SMTPSecure = "tls";
		$mail->Port = 587;
		$mail->From = "info@simulationnarrative.cf";
		$mail->FromName = "Saga+";
		$mail->addAddress($_REQUEST['email']);
		$mail->Subject = "Vos informations de connexion";
		$mail->Body = "Login : ".$_REQUEST['email']."\nMot de passe : ".$pass;
		$mail->send();


  		$data['msg_success'][] = "Votre compte a été créé avec succès";
  		$data['msg_success'][] = "Un mot de passe vous a été envoyé par email.";
	}

	echo $blade->view()->make('login',$data)->render();
	die();

}

if(isset($_REQUEST['logout'])){
	session_destroy();
	header('Location: /');
	die();
}

if(isset($_REQUEST['login'])){
	if (!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
		$data['msg_errors'][] = "E-mail invalide";
	}else if(db_count(db_s('users', array('email' => $_REQUEST['email'])))==0){
		$data['msg_errors'][] = "Aucun compte n'existe pour cette adresse email.";
	}else{
		$u = db_fetch(db_s('users', array('email' => $_REQUEST['email'])));
		if($u['password_hash'] == sha1($_REQUEST['password'].$key)){
			$_SESSION['logged'] = $u;
			db_u('users', array('id' => $u['id']), array('last_login' => date('Y-m-d H:i:s')));
		}else{
			$data['msg_errors'][] = "Mot de passe invalide";
		}
	}
}
if(isset($_REQUEST['logout'])){
	unset($_SESSION['logged']);
}

if(!isset($_SESSION['logged'])){
	echo $blade->view()->make('login',$data)->render();
	die();
}