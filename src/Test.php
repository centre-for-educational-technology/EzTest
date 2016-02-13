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
		
		return self::RenderQuestion( $App, $Question );
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
	
	public static function DisplayAllQuestions( $Request, $Response, $Service, $App )
	{
		echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">';
		
		$Questions = $App->Database->query( 'SELECT `QuestionID`, `Type`, `Stimulus` FROM `questions` ORDER BY `Type`' )->fetchAll();
		
		echo '<ul>';
		
		foreach( $Questions as $Question )
		{
			echo '<li><a href="/question/' . $Question->QuestionID . '">Question #' . $Question->QuestionID. ': <b> type ' . $Question->Type . '</b></a><div class="well">' . $Question->Stimulus . '</div></li>';
		}
		
		echo '</ul>';
	}
	
	public static function HandleRender( $Request, $Response, $Service, $App )
	{
		$files = glob('../qtifiles/interactions/*.xml');
		
		$questions = [];
		
		$NewQuestion = $App->Database->prepare(
			'INSERT INTO `questions` (`Type`, `Stimulus`, `Data`, `Hash`) ' .
			'VALUES (:type, :stimulus, :data, :hash)'
		);
		
		foreach( $files as $file )
		{
			try
			{
				$xmlString = file_get_contents( $file );
				$converted = Converter::convertQtiItemToLearnosity($xmlString);
				
				$questions = array_merge( $questions, $converted[ 1 ] );
				
				foreach( $converted[ 1 ] as $Data )
				{
					$Data = $Data[ 'data' ];
					$Type = $Data[ 'type' ];
					$Stimulus = isset( $Data[ 'stimulus' ] ) ? $Data[ 'stimulus' ] : '';
					
					unset( $Data[ 'stimulus' ], $Data[ 'type' ] );
					
					$Data = json_encode( $Data );
					
					$NewQuestion->bindValue( ':type', $Type );
					$NewQuestion->bindValue( ':stimulus', $Stimulus );
					$NewQuestion->bindValue( ':data', $Data );
					$NewQuestion->bindValue( ':hash', md5( $Data ) );
					$NewQuestion->execute();
					
					unset( $Data, $Type, $Stimulus );
				}
			}
			catch( \Exception $e )
			{
				echo 'Failed to convert ' . $file . PHP_EOL;
			}
		}
	}
	
	private static function RenderQuestion( $App, $Question )
	{
		$Data = json_decode( $Question->Data, true );
		
		if( $Question->Type === 'clozeassociation' )
		{
			$Responses = [ '<option selected="selected" value="-1"></option>' ];
			
			foreach( $Data[ 'possible_responses' ] as $Key => $Response )
			{
				$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
			}
			
			$Responses = '<select id="question_' . $questionid . '_answer" data-inline="true">' . implode( '', $Responses ) . '</select>';
			
			$Data[ 'template' ] = str_replace( '{{response}}', $Responses, $Data[ 'template' ] );
		}
		
		return $App->Twig->render( 'questions/question.html', [
			'question' => $Question,
			'data' => $Data,
			'title' => 'Question - ' . \System\Config::$SystemName,
		] );
		
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
