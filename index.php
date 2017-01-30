<?php
error_reporting(E_ALL);
session_start();
include "core.php";

class showPage {
	function __construct($seitenname, $templates) {
		$page = $this->ladeSeite("header", $templates);
		$page .= $this->ladeSeite("$seitenname", $templates);
		$page .= $this->ladeSeite("footer", $templates);
		
	}

	private function ersetzeTemplate($file, $template, $realExpression) {
		return str_replace($template, $realExpression, $file);
	}

	private function ladeSeite ($page, $template) {
		$file = file_get_contents("templates/$page.inc.php");
		if(isset($template)) {
			$template = unserialize($template);
			$i = 0;
			while(count($template['old']) > $i) {
				$file = $this->ersetzeTemplate($file, $template['old'][$i], $template['new'][$i]);
				$i++;
			}
		}
		echo $file;
	}

}

class index extends Core {

	function __construct() {
		$this->decideWhatToDo();
	}

	public function decideWhatToDo() {
		$getposttoken = "";
		if(isset($_GET['token'])) {
			$getposttoken = $_GET['token'];
		} elseif(isset($_POST['token'])) {
			$getposttoken = $_POST['token'];
		}

		if($getposttoken != "") {
			if(!isset($_SESSION["logintoken"])) {
				$_SESSION["logintoken"] = $getposttoken;
			}
		}

		if(!isset($_SESSION['loggedin'])) {
			if(!isset($_POST['action']) AND (!isset($_GET['action']))) {
				$this->showLoginForm("");
			}
		}

		if(isset($_POST['action']) AND ($_POST['action'] == "login")) {
			$this->validateLoginForm($_POST['loginname'], $_POST['loginpassword'], $_POST['token']);
		}

		if(isset($_GET['action'])) {
			//Perfom GET Actions here
			if($_GET['action'] == "logout") {
				$this->logout();
			} elseif($_GET['action'] == "register") {
				$this->zeigeRegistrierung();
			
			} elseif($_GET['action'] == "about") {
				$this->zeigeAbout();
			}


		} elseif(isset($_SESSION['loggedin']) AND ($_SESSION['loggedin'] == true)) {
			$hauptmenuzeigen = true;

			if (isset($_GET['view']) AND $_GET['view'] == "profile") {
				if(isset($_GET['username'])) {
					$hauptmenuanzeigen = false;
					$apireturn = $this->curl_download('api.php?format=php&page=getUserProfile&username=' . $_GET['username'] . '&token=' . $_SESSION['logintoken'] . '&ipadress=' . $_SERVER['REMOTE_ADDR']);
					$apireturn = unserialize($apireturn);
					$this->showProfile($apireturn);

				} else {
					echo "Kein benutzername.";
					//Kein Benutzername angegeben.
				}
				$hauptmenuzeigen = false;
			}
			if(isset($_POST['action']) AND $_POST['action'] == "submitComment") {

				if($_POST['comment'] != "") {
					$this->curl_download('api.php?format=php&page=commentPost&postid=' . $_POST['relatedPostID'] . '&comment=' . $_POST['comment'] . '&token=' . $_SESSION["logintoken"] . '&ipadress=' . $_SERVER['REMOTE_ADDR']	);
				}
			}
			if(isset($_POST['action']) AND $_POST['action'] == "sendPost") {
				if($_POST['content'] != "") {
					$this->curl_download('api.php?format=php&page=sendPost&content=' . $_POST['content'] . '&token=' . $_SESSION["logintoken"] . '&ipadress=' . $_SERVER['REMOTE_ADDR']	);
				}
			}

			if($hauptmenuzeigen == true) {
				$this->zeigeHauptMenu();
			}

		}

	}

	public function zeigeRegistrierung() {
		$page = "register";
		$pagetitle = "Registrieren";
		$template = array("old" => array("{pagetitle}", ""), "new" => array("$pagetitle", ""));
		$this->gebeSeiteAus($page, $template);

	}

		public function zeigeAbout() {
		$page = "about";
		$pagetitle = "Über";
		$template = array("old" => array("{pagetitle}", ""), "new" => array("$pagetitle", ""));
		$this->gebeSeiteAus($page, $template);

	}

	public function logout() {
		if(isset($_SESSION["logintoken"])) {
			if(isset($_SESSION['loggedin'])) {

				$curl = $this->curl_download('api.php?format=php&page=logOut&token=' . $_SESSION["logintoken"] . '&ipadress=' . $_SERVER['REMOTE_ADDR']) ;
				unset($_SESSION['loggedin']);
				unset($_SESSION['logintoken']);
		
				$page = "logout";
				$pagetitle = "Ausgeloggt";
				$template = array("old" => array("{pagetitle}", ""), "new" => array("$pagetitle", ""));
				$this->gebeSeiteAus($page, $template);
			} else {
				
			}
		} else {
			echo "Kein Token zum Ausloggen übergeben";
		}
	}

	public function holePosts($posts) {
		$postcontentdiv = "";
		$i = 0;
		while(isset($posts['posts'][$i])) {
			$date = substr($posts['posts'][$i]['date'], 0, 10) . " um" . substr($posts['posts'][$i]['date'], 10);
			$postcontentdiv .= "<div class='post' id='post" . $posts['posts'][$i]['id'] . "'><a href='?view=profile&username=" . $posts['posts'][$i]['username'] . "'>" . $posts['posts'][$i]['vorname'] . " " . $posts['posts'][$i]['nachname'] . "</a> schrieb am " . $date . ":<br />" .  $posts['posts'][$i]['postcontent'];
			$postcontentdiv .= "<div id='commentsarea'>";
			
			$comments = $this->curl_download('api.php?format=php&page=getCommentsByPost&token=' . $_SESSION["logintoken"]	 . '&ipadress=' . $_SERVER['REMOTE_ADDR'] . '&postid=' . $posts['posts'][$i]['id']);
			$comments = unserialize($comments);
			$j = 0;
			while(isset($comments['comments'][$j])) {
				$postcontentdiv .= "<div id='comment'>";
				$postcontentdiv .= $comments['comments'][$j]['commentid'] . ": <a href='?view=profile&username=" . $comments['comments'][$j]['username'] . "'>" . $comments['comments'][$j]['vorname'] . " " . $comments['comments'][$j]['nachname'] . "</a> um " . $comments['comments'][$j]['date'] . ":<br />" . $comments['comments'][$j]['comment'];

				$postcontentdiv .= "</div>\n";
				$j++;
			}
			if(isset($comments['fail']['notice'])) {
				if($comments['fail']['notice'] == "nocomment") {
					$postcontentdiv .= "<div id='comment'><span id='nocomments'>Es sind keine Kommentare zu diesem Beitrag vorhanden.</span></div>";
				}
			}
			$postcontentdiv .= "<form method='post' action='#post" . $posts['posts'][$i]['id'] . "'><input type='hidden' name='action' value='submitComment'><input type='hidden' name='relatedPostID' value='" . $posts['posts'][$i]['id'] . "'><textarea name='comment'></textarea><input type='submit' value='Kommentieren!'> </form>";
			$postcontentdiv .= "</div>";
			$postcontentdiv .= "</div>\n";
			$i++;
		}
		return $postcontentdiv;
	}

	public function zeigeHauptMenu() {

		$postingarea = "Poste einen neuen Post...<form method='post'><input type='hidden' name='action' value='sendPost'><textarea name='content' placeholder='Was ist gerade los?'></textarea><input type='submit' value='Posten!'></form>";

		$posts = $this->curl_download('api.php?format=php&page=getPostsList&token=' . $_SESSION["logintoken"]	 . '&ipadress=' . $_SERVER['REMOTE_ADDR']);
		$posts = unserialize($posts);

		if($posts['success']['state'] == "success") {
			$postcontent = $this->holePosts($posts);
		} else {
			$postcontent = "Leider ist ein Fehler aufgetreten. :((";
		}	
		$getuser = $this->curl_download('api.php?format=php&page=getUserByToken&token=' . $_SESSION["logintoken"] . '&ipadress=' . $_SERVER['REMOTE_ADDR']);
		$getuser = unserialize($getuser);
		$loggedinas = $getuser['user']['vorname'];
		$page = "mainmenu";
		$pagetitle = "Übersicht";
		$template = array("old" => array("{pagetitle}", "{posts}", "{loggedin}", "{postingarea}",), "new" => array("$pagetitle", "$postcontent", "$loggedinas", "$postingarea",));
		$this->gebeSeiteAus($page, $template);
	}

	public function showProfile($apireturn) {
		$page = "profile";
		$pagetitle = "Profil anzeigen";

		if($apireturn['success']['state'] == "fail") {
			$template = array("old" => array("{pagetitle}","{vorname}","{nachname}","{bio}", "{usercontribs}"), "new" => array($pagetitle, "Es wurde kein Profil gefunden...", "","Hier gibt es nichts", ""));
		} else {

			$curl = $this->curl_download("api.php?format=php&page=getPostsByUsername&username=" . $apireturn['userprofile']['username']  . "&token=" . $_SESSION['logintoken'] . "&ipadress=" . $_SERVER['REMOTE_ADDR']);

			$curl = unserialize($curl);

			$i = 0;
			$poststring = "";
			while($i < count($curl['posts'])) {
					$poststring .= "<div>&bull; hat einen Post am " . $curl['posts'][$i]['date'] . " verfasst:<br/>" . $curl['posts'][$i]['postcontent'] . "</div>";
				$i++;
			}

			$template = array("old" => array("{pagetitle}","{vorname}","{nachname}","{bio}", "{usercontribs}"), "new" => array($pagetitle, $apireturn['userprofile']['vorname'], $apireturn['userprofile']['nachname'],$apireturn['userprofile']['description'], $poststring));
		}


		
		$this->gebeSeiteAus($page, $template);
	}

	public function showLoginForm($fehlermeldung) {
		$page = "login";
		$pagetitle = "Anmelden";
		$token = $this->curl_download('api.php?format=php&page=getLoginToken&ipadress=' . $_SERVER['REMOTE_ADDR']) ;
		$token = unserialize($token);
		$token = $token['token']['token'];
		$template = array("old" => array("{pagetitle}","{token}", "{fehlermeldung}"), "new" => array("$pagetitle", "$token", "$fehlermeldung"));
		$this->gebeSeiteAus($page, $template);
	}

	public function validateLoginForm($username, $password, $tid) {
		$fehlt = "";
		if((!isset($username)) OR ($username == "")) {
			$fehlt = "Benutzername";
		} elseif((!isset($password)) OR ($password == "")) {
			$fehlt = "Passwort";
		} elseif(!isset($tid)) {
			$fehlt = "Logintoken";
		}

		if($fehlt != "") {
			$this->showLoginForm("<span style='color:red;'>Fehler: $fehlt fehlt.</span>");
		} else {
			$return = $this->curl_download("api.php?format=php&page=validateLogin&username=" . $username . "&password=" . $password . "&token=" . $tid . "&ipadress=" . $_SERVER['REMOTE_ADDR']);
			$return = unserialize($return);
			if(isset($return['fail']['state']) AND ($return['fail']['state'] == "fail")) {
				$this->showLoginForm("<span style='color:red;'>Fehler: " . $return['fail']['notice'] . "</span>");
			} else {
				if($return['login']['loginstate'] == "success") {
					$_SESSION['loggedin'] = true;
					//return "Login erfolgreich";
				}
			}
		}
	}

	protected function gebeSeiteAus($seite, $template) {
		$index = new showPage($seite, serialize($template));
	}
}
$index = new index();

shell_exec("./getfromgit.sh");

?>
