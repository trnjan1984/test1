<?php
/**
 * Static. Manipulates alerts that are created trough the execution of web
 * application scripts.
 */
class Dump
{
/**
 * Holds the confirmation messages.
 * @var array
 */
	static private $messages = array();
/**
 * Holds the error messages.
 * @var array
 */
	static private $errors   = array();
/**
 * Holds the info messages.
 * @var array
 */
	static private $dumps    = array();
	static private $aaa; 
	
	static private $fp;
	
	static private $checkDelete = 0;
	static private $fileDir = '/var/www/html/temp/';
	

/**
 * Static. Creates html to display all the alerts.
 *
 * @access public
 * @return string $html Html to display alerts.
 */
	public static function getDumps()
	{
		$html = '';

		
		if (!empty(self::$messages) && !empty(self::$dumps))
		{	
			if(count(self::$messages) == count(self::$dumps))
			{
				for($i = 0; $i < count(self::$messages); $i++)
				{
					$html .= '<div class="ui-state-message ui-corner-all"><p>' . "Message: <pre>" . self::$messages[$i] . '</pre></p></div><br />';
					$html .= '<div class="ui-state-info ui-corner-all"><p>' . "DUMPS -> <br /><pre>" . self::$dumps[$i] . '</pre></p></div><br />';
				}
			}
		}
		
		if (!empty(self::$errors))
			$html .= '<div class="ui-state-error ui-corner-all"><p>' . join('<br />', self::$errors) . '</p></div>';
		
		if ($html !== '')
			$html = '<div class="ui-widget alert-container">' . $html . '</div>';
		
		return $html;
	}
	
	public static function getDump($dump)
	{
		$dump = print_r($dump, TRUE);
		$html = '<div class="ui-state-info ui-corner-all"><p>' . "DUMP -> <br />" . $dump . '</p></div>';
		return $html;
	}

	public static function saveToFile()
	{
		$name = "dumps";
		
		$fp = fopen(self::$fileDir . $name . '.txt', 'w'); 
		
		if (count(self::$messages) == count(self::$dumps))
			{	
				for($i = 0; $i < count(self::$messages); $i++)
				{
					$write = self::$messages[$i] . "\n\r" . self::$dumps[$i] . "\n\--BR--\n\r";
					
					fwrite($fp, $write);
				}
			}	
		else
			echo "NO DUMPS!";

		fclose($fp);
	}

/**
 * Static. Appends an info alert to self::$infos.
 *
 * @access public 
 * @param string $alertString A string to append to alerts.
 * @return void
 */  
	public static $varijabla = "bla bla bla";
	
	public static function sendMail($body, $subyectAdd, $customEmail = '')
	{
		$subject = 'Dump mail - ' . $subyectAdd;
		
		if(preg_match('/^([a-z0-9]+([_\.\-]{1}[a-z0-9]+)*){1}([@]){1}([a-z0-9]+([_\-]{1}[a-z0-9]+)*)+(([\.]{1}[a-z]{2,6}){0,3}){1}$/i', $customEmail))
			$mail = $customEmail;
		else
			$mail = 'tomislav.cipric@tango.hr';
		
		$senderName = 'Dump sender';
		$senderEmail = 'reporter_noreply@tango.com.hr';
		$mailer = new PHPMailer(true);
		$mailer->IsHTML();
		$mailer->IsSMTP();
		$mailer->Host = Config::SMTP_SERVER;     // sets the SMTP server
		$mailer->Subject = $subject;	
		
		$mailer->MsgHTML($body);

		$mailer->SetFrom($senderEmail,$senderName);
		$mailer->AddAddress($mail);	
		$mailer->AddBCC('tomislav.cipric@tango.hr');
		self::object($customEmail, __FILE__ . " - " . __LINE__ . " - " .  'abcdefaa<br />' . $body .'<br />');

		try 
		{
			$mailer->Send();
		}
		catch (Exception $e) 
		{
		    echo 'Caught exception: ',  $e->getMessage(), "\n";
		}

		$mailer->ClearAddresses();		

		//self::object('body', __FILE__ . " - " . __LINE__ . " - " .  '<br />' . $body .'<br />');

	}

	public static function object($dumpObject, $dumpMessage, $isHtml = false, $sendOnMail = '')
	{ 

		$name = "dumps";
		$filedir = self::$fileDir . $name . '.txt';
		if (!file_exists($filedir)) 
		{
			$ourFileHandle = fopen($filedir, 'w+') or die("Can't create Dump file check for permissions");
			fclose($ourFileHandle);
		}
	
		$messages = $dumpMessage;
		$dumps = print_r($dumpObject, TRUE);


		$name = "dumps";
		if (self::$checkDelete == 1 )
		{
			$fp = fopen($filedir, 'a'); 
			
		}	
		else
		{
			$fp = fopen($filedir, 'w');

		}
			
		if($isHtml)
			$write = "<b>" . date("D M j G:i:s T Y") . " - " . $messages . "</b>\n\n" . $dumpObject . "\n--BR--\n\n";
		else
			$write = "<b>" . date("D M j G:i:s T Y") . " - " . $messages . "</b>\n\n<pre>" . htmlspecialchars($dumps) . "</pre>\n--BR--\n\n";
			
		if($sendOnMail)
		{
			self::sendMail($write, $messages, $sendOnMail);
		}
		fwrite($fp, $write);
		fclose($fp);
		self::$checkDelete = 1;

	}
	

	
	public static function liveDump($object)
	{
		echo "<br /><pre>";
		var_dump($object);
		echo "<br /></pre>";		
	}	

	
}

?>