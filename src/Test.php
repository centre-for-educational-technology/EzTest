<?php
namespace System;

use LearnosityQti\Converter;

class Test
{
	public static function PreviewQuestion( $Request, $Response, $Service, $App )
	{
		$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus`, `Data` FROM `questions` WHERE `UserID` = :userid AND `QuestionID` = :id' );
		$Question->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Question->bindValue( ':id', $Request->ID, \PDO::PARAM_INT );
		$Question->execute();
		$Question = $Question->fetch();
		
		if( !$Question )
		{
			$Response->code( 404 );
			
			return 'Question not found';
		}
		
		return $App->Twig->render( 'questions/question.html', [
			'question' => $Question,
			'data' => self::GetQuestionData( $App, $Question ),
		] );
	}
	
	public static function RenderEmail( $Request, $Response, $Service, $App )
	{
		$URL = 'http://';
		
		if (isset($_SERVER['HTTPS']) &&
			($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
			isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		)
		{
			$URL = 'https://';
		}
		
		$URL .= $_SERVER[ 'HTTP_HOST' ];
		$URL .= '/private/wolololol';
		
		return $App->Twig->render( 'emails/new_test.html', [
			'TestURL' => $URL,
			'AssignmentNote' => 'This is a really simple email template. Its sole purpose is to get you to click the button below.',
			'AdminName' => 'Lecturer Name',
			'UserName' => 'Email Reciever',
			'AssignmentName' => 'Some IT stuff',
			'TestName' => 'Cool test',
		] );
	}
	
	public static function RenderPrivateTest( $Request, $Response, $Service, $App )
	{
		$Hash = $Request->Hash;
		
		$Assignment = $App->Database->prepare(
			'SELECT `assignments_users`.`AssignmentID`, `assignments`.`Name` as `AssignmentName`, `tests`.`Name` as `TestName`, `users`.`Name` as `UserName`, `users`.`Email`, `users`.`UserID`, `tests`.`TestID`, `assignments_users`.`AssignmentID` FROM `assignments_users` ' .
			'JOIN `assignments` ON `assignments_users`.`AssignmentID` = `assignments`.`AssignmentID` ' .
			'JOIN `tests` ON `assignments`.`TestID` = `tests`.`TestID` ' .
			'JOIN `users` ON `assignments_users`.`UserID` = `users`.`UserID` ' .
			'WHERE `Hash` = :hash'
		);
		$Assignment->bindValue( ':hash', $Hash );
		$Assignment->execute();
		$Assignment = $Assignment->fetch();
		
		if( !$Assignment )
		{
			$Response->code( 404 );
			
			return 'This assignment does not exist.';
		}
		
		$Questions = $App->Database->prepare( 'SELECT `QuestionID`, 0 FROM `tests_questions` WHERE `TestID` = :id ORDER BY `Order`' );
		$Questions->bindValue( ':id', $Assignment->TestID, \PDO::PARAM_INT );
		$Questions->execute();
		$Assignment->Questions = $Questions->fetchAll( \PDO::FETCH_KEY_PAIR );
		
		$_SESSION[ $Hash ] = $Assignment;
		
		return $App->Twig->render( 'questions/newname.html', [
			'session' => $Assignment,
		] );
	}
	
	public static function HandlePrivateTest( $Request, $Response, $Service, $App )
	{
		$Hash = $Request->Hash;
		
		if( !isset( $_SESSION[ $Hash ] ) )
		{
			$Response->code( 400 );
			
			return 'No assignment session.';
		}
		
		if( empty( $_SESSION[ $Hash ]->UserName ) )
		{
			$Response->code( 400 );
			
			return 'Your name is not set.';
		}
		
		$Session = $_SESSION[ $Hash ];
		
		if( !isset( $_POST[ 'action' ] ) )
		{
			$Response->code( 400 );
			
			return 'Missing action.';
		}
		
		$Action = $_POST[ 'action' ];
		
		if( $Action === 'finish' )
		{
			// TODO: Send email with final scores
			
			return '<div role="main" class="ui-content">Thanks! :)</div>';
		}
		
		if( $Action === 'setname' )
		{
			$Name = filter_input( INPUT_POST, 'newname', FILTER_SANITIZE_STRING );
			
			if( strlen( $Name ) < 1 )
			{
				$Response->code( 400 );
				
				return 'Missing name.';
			}
			
			$STH = $App->Database->prepare( 'UPDATE `users` SET `Name` = :name WHERE `UserID` = :userid' );
			$STH->bindValue( ':userid', $Session->UserID, \PDO::PARAM_INT );
			$STH->bindValue( ':name', $Name );
			$STH->execute();
			
			$_SESSION[ $Hash ]->UserName = $Name;
			
			$Action = 'begin';
		}
		
		if( $Action === 'submitanswer' )
		{
			if( !isset( $Session->Questions[ $_POST[ 'question_id' ] ] ) )
			{
				$Response->code( 400 );
				
				return 'No such question.';
			}
			
			$Session->Questions[ $_POST[ 'question_id' ] ] = 1;
			$_SESSION[ $Hash ]->Questions = $Session->Questions;
			
			self::HandleQuestionAnswer( $Request, $Response, $Service, $App, $Session );
			
			$Action = 'begin';
		}
		
		if( $Action === 'begin' )
		{
			$NextQuestionID = 0;
			$CurrentQuestionIndex = 0;
			
			foreach( $Session->Questions as $QuestionID => $Solved )
			{
				$CurrentQuestionIndex++;
				
				if( !$Solved )
				{
					$NextQuestionID = $QuestionID;
					break;
				}
			}
			
			if( $NextQuestionID === 0 )
			{
				return $App->Twig->render( 'questions/finish.html', [
					'session' => $Session,
				] );
			}
			
			$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus`, `Data` FROM `questions` WHERE `QuestionID` = :id' );
			$Question->bindValue( ':id', $NextQuestionID, \PDO::PARAM_INT );
			$Question->execute();
			$Question = $Question->fetch();
			
			$CurrentAnswer = $App->Database->prepare( 'SELECT `Answer` FROM `assignments_answers` WHERE `UserID` = :userid AND `AssignmentID` = :assignmentid AND `QuestionID` = :questionid' );
			$CurrentAnswer->bindValue( ':userid', $Session->UserID, \PDO::PARAM_INT );
			$CurrentAnswer->bindValue( ':questionid', $NextQuestionID, \PDO::PARAM_INT );
			$CurrentAnswer->bindValue( ':assignmentid', $Session->AssignmentID, \PDO::PARAM_INT );
			$CurrentAnswer->execute();
			$CurrentAnswer = $CurrentAnswer->fetch();
			
			if( $CurrentAnswer )
			{
				$CurrentAnswer = json_decode( $CurrentAnswer->Answer, true );
			}
			
			return $App->Twig->render( 'questions/question.html', [
				'session' => $Session,
				'question' => $Question,
				'current_answer' => $CurrentAnswer,
				'current_question' => $NextQuestionID,
				'current_question_index' => $CurrentQuestionIndex,
				'data' => self::GetQuestionData( $App, $Question ),
			] );
		}
		else
		{
			$Response->code( 400 );
			
			return 'Unknown action.';
		}
	}
	
	private static function HandleQuestionAnswer( $Request, $Response, $Service, $App, $Session )
	{
		$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Data` FROM `questions` WHERE `QuestionID` = :id' );
		$Question->bindValue( ':id', $_POST[ 'question_id' ], \PDO::PARAM_INT );
		$Question->execute();
		$Question = $Question->fetch();
		
		$Data = json_decode( $Question->Data, true );
		
		$Score = 0;
		$ProvidedAnswer = '';
		
		switch( $Question->Type )
		{
			case 'mcq':
			{
				$ProvidedAnswer = filter_input(
					INPUT_POST,
					'question_answer',
					FILTER_DEFAULT,
					isset( $Data[ 'multiple_responses' ] ) ? FILTER_REQUIRE_ARRAY : 0
				);
				
				var_dump( $ProvidedAnswer );
				
				// TODO: Handle Partial Match
				// TODO: Handle alt_responses
				// TODO: Handle scoring
				if( $Data[ 'validation' ][ 'scoring_type' ] === 'exactMatch' )
				{
					$CorrectAnswer = $Data[ 'validation' ][ 'valid_response' ][ 'value' ];
					
					if( isset( $Data[ 'multiple_responses' ] ) )
					{
						foreach( $CorrectAnswer as $Answer )
						{
							$ProvidedAnswerFound = array_search( $Answer, $ProvidedAnswer );
							
							if( $ProvidedAnswerFound !== false )
							{
								unset( $ProvidedAnswer[ $ProvidedAnswerFound ] );
								
								//echo '<h1><b>' . $Answer . '</b> is correct!</h1>';
								$Score = $Data[ 'validation' ][ 'valid_response' ][ 'score' ];
							}
						}
						
						foreach( $ProvidedAnswer as $Answer )
						{
							//echo '<b>' . $Answer . '</b> is an incorrect response<br>';
							$Score = 0;
						}
					}
					else
					{
						if( $CorrectAnswer[ 0 ] === $ProvidedAnswer )
						{
							$Score = $Data[ 'validation' ][ 'valid_response' ][ 'score' ];
							//echo '<h1>You answered correctly!</h1>';
						}
						else
						{
							//echo 'Invalid answer. You answered: <b>' . $ProvidedAnswer . '</b>, correct answer is: <b><u>' . $CorrectAnswer[ 0 ] . '</u></b>';
						}
					}
				}
				
				break;
			}
			case 'longtext':
			{
				$ProvidedAnswer = filter_input(
					INPUT_POST,
					'question_answer',
					FILTER_SANITIZE_STRING
				);
				
				if( strlen( $ProvidedAnswer ) > 0 )
				{
					$Score = 1;
				}
			}
			case 'clozeassociation':
			{
				
			}
		}
		
		$STH = $App->Database->prepare(
			'INSERT INTO `assignments_answers` (`UserID`, `AssignmentID`, `QuestionID`, `Score`, `Answer`) ' .
			'VALUES (:userid, :assignmentid, :questionid, :score, :answer) ' .
			'ON DUPLICATE KEY UPDATE `Score` = VALUES(`Score`), `Answer` = VALUES(`Answer`)'
		);
		$STH->bindValue( ':userid', $Session->UserID, \PDO::PARAM_INT );
		$STH->bindValue( ':questionid', $_POST[ 'question_id' ], \PDO::PARAM_INT );
		$STH->bindValue( ':assignmentid', $Session->AssignmentID, \PDO::PARAM_INT );
		$STH->bindValue( ':score', $Score, \PDO::PARAM_INT );
		$STH->bindValue( ':answer', json_encode( $ProvidedAnswer ) );
		$STH->execute();
		
		var_dump($Score);
		echo '<pre>';
		print_r( $_POST );
		print_r( $Data );
		echo '</pre>';
	}
	
	private static function GetQuestionData( $App, $Question )
	{
		$Data = json_decode( $Question->Data, true );
		
		if( $Question->Type === 'clozeassociation' )
		{
			$Responses = [ '<option selected="selected" value="-1" disabled></option>' ];
			
			foreach( $Data[ 'possible_responses' ] as $Key => $Response )
			{
				$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
			}
			
			$Counter = 0;
			$Data[ 'template' ] = preg_replace_callback( '/{{response}}/', function() use ( $Responses, &$Counter )
			{
				return '<select name="question_answer_' . $Counter . '" data-inline="true" required>' . implode( '', $Responses ) . '</select>';
				
				$Counter++;
			}, $Data[ 'template' ] );
		}
		else if( $Question->Type === 'clozedropdown' )
		{
			$Counter = 0;
			
			foreach( $Data[ 'possible_responses' ] as $PossibleResponses )
			{
				$Responses = [ '<option selected="selected" value="-1" disabled></option>' ];
				
				foreach( $PossibleResponses as $Key => $Response )
				{
					$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
				}
				
				$Responses = '<select name="question_answer_' . $Counter . '" data-inline="true" required>' . implode( '', $Responses ) . '</select>';
				$Counter++;
				
				$Position = strpos( $Data[ 'template' ], '{{response}}' );
				
				if( $Position !== false )
				{
					$Data[ 'template' ] = substr_replace( $Data[ 'template' ], $Responses, $Position, strlen( '{{response}}' ) );
				}
			}
		}
		else if( $Question->Type === 'mcq' )
		{
			if( isset( $Data[ 'shuffle_options' ] ) && $Data[ 'shuffle_options' ] )
			{
				shuffle( $Data[ 'options' ] );
			}
		}
		
		return $Data;
	}
}
