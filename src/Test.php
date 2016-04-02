<?php
namespace System;

use LearnosityQti\Converter;

class Test
{
	public static function DisplayQuestion( $Request, $Response, $Service, $App )
	{
		$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus`, `Data` FROM `questions` WHERE `QuestionID` = :id' );
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
		return $App->Twig->render( 'emails/new_test.html', [
			'hash' => 'wolololol',
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
			'assignment' => $Assignment,
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
			if( !isset( $Session->Questions[ $_POST[ 'questionid' ] ] ) )
			{
				$Response->code( 400 );
				
				return 'No such question.';
			}
			
			$Session->Questions[ $_POST[ 'questionid' ] ] = 1;
			$_SESSION[ $Hash ]->Questions = $Session->Questions;
			
			$Action = 'begin';
		}
		
		if( $Action === 'begin' )
		{
			$NextQuestionID = -1;
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
			
			$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus`, `Data` FROM `questions` WHERE `QuestionID` = :id' );
			$Question->bindValue( ':id', $NextQuestionID, \PDO::PARAM_INT );
			$Question->execute();
			$Question = $Question->fetch();
			
			return $App->Twig->render( 'questions/question.html', [
				'session' => $Session,
				'question' => $Question,
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
	
	public static function HandleQuestionAnswer( $Request, $Response, $Service, $App )
	{
		$Question = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Data` FROM `questions` WHERE `QuestionID` = :id' );
		$Question->bindValue( ':id', $Request->ID, \PDO::PARAM_INT );
		$Question->execute();
		$Question = $Question->fetch();
		
		if( !$Question )
		{
			$Response->code( 404 );
			
			return;
		}
		
		$Data = json_decode( $Question->Data, true );
		
		switch( $Question->Type )
		{
			case 'mcq':
			{
				$ProvidedAnswer = filter_input(
					INPUT_POST,
					'question_' . $Question->QuestionID . '_answer',
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
								
								echo '<h1><b>' . $Answer . '</b> is correct!</h1>';
							}
						}
						
						foreach( $ProvidedAnswer as $Answer )
						{
							echo '<b>' . $Answer . '</b> is an incorrect response<br>';
						}
					}
					else
					{
						if( $CorrectAnswer[ 0 ] === $ProvidedAnswer )
						{
							echo '<h1>You answered correctly!</h1>';
						}
						else
						{
							echo 'Invalid answer. You answered: <b>' . $ProvidedAnswer . '</b>, correct answer is: <b><u>' . $CorrectAnswer[ 0 ] . '</u></b>';
						}
					}
				}
				
				break;
			}
		}
		
		echo '<hr><pre>';
		if( !empty( $_POST ) )
		{
			print_r( $_POST );
		}
		print_r( $Data );
		echo '</pre>';
	}
	
	private static function GetQuestionData( $App, $Question )
	{
		$Data = json_decode( $Question->Data, true );
		
		if( $Question->Type === 'clozeassociation' )
		{
			$Responses = [ '<option selected="selected" value="-1"></option>' ];
			
			foreach( $Data[ 'possible_responses' ] as $Key => $Response )
			{
				$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
			}
			
			$Responses = '<select id="question_' . $Question->QuestionID . '_answer" data-inline="true">' . implode( '', $Responses ) . '</select>';
			
			$Data[ 'template' ] = str_replace( '{{response}}', $Responses, $Data[ 'template' ] );
		}
		else if( $Question->Type === 'clozedropdown' )
		{
			foreach( $Data[ 'possible_responses' ] as $PossibleResponses )
			{
				$Responses = [ '<option selected="selected" value="-1"></option>' ];
				
				foreach( $PossibleResponses as $Key => $Response )
				{
					$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
				}
				
				$Responses = '<select id="question_' . $Question->QuestionID . '_answer" data-inline="true">' . implode( '', $Responses ) . '</select>';
				
				$Position = strpos( $Data[ 'template' ], '{{response}}' );
				
				if( $Position !== false )
				{
					$Data[ 'template' ] = substr_replace( $Data[ 'template' ], $Responses, $Position, strlen( '{{response}}' ) );
				}
			}
		}
		
		return $Data;
		
		$questionid = $Question->QuestionID;
		
		if( $Question->Type === 'mcq' )
		{
			if( isset( $Data[ 'shuffle_options' ] ) && $Data[ 'shuffle_options' ] )
			{
				shuffle( $Data[ 'options' ] );
			}
		}
		else if( $Question->Type === 'longtext' )
		{
			// Maximum number of words that can be entered in the field.
			$MaxLength = isset( $Data[ 'max_length' ] ) ? (int)$Data[ 'max_length' ] : 10000;
		}
		else
		{
			echo '<pre>';
			print_r($Data);
			echo '</pre>';
		}
	}
}
