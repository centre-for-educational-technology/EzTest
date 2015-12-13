<?php
namespace System;

use LearnosityQti\Converter;

class Test
{
	public static function HandleAnswer( $request, $response, $service )
	{
		echo '<pre>';
		if( !empty( $_POST ) )
		{
			print_r( $_POST );
		}
		echo '</pre>';
	}
	
	public static function HandleRender( $request, $response, $service )
	{
		$files = glob('../qtifiles/interactions/*.xml');
		
		$questions = [];
		
		foreach( $files as $file )
		{
			try
			{
				$xmlString = file_get_contents( $file );
				$converted = Converter::convertQtiItemToLearnosity($xmlString);
				
				$questions = array_merge( $questions, $converted[ 1 ] );
				
			}
			catch( \Exception $e )
			{
				echo 'Failed to convert ' . $file . PHP_EOL;
			}
			
		}

		echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">';
		echo '<script type="text/javascript" src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>';
		
		$questionid = 0;

		echo '<form method="POST" class="container">';

		// https://docs.learnosity.com/authoring/qti/index
		// https://docs.learnosity.com/assessment/questions/questiontypes#mcq
		foreach( $questions as $question )
		{
			$questionid++;
			
			echo '<h3 class="text-muted">Question #' . $questionid . ':</h3><h4>' . ( isset( $question[ 'data' ][ 'stimulus' ] ) ? $question[ 'data' ][ 'stimulus' ] : '' ) . '</h4>';
			
			if( $question[ 'type' ] === 'mcq' )
			{
				if( isset( $question[ 'data' ][ 'shuffle_options' ] ) && $question[ 'data' ][ 'shuffle_options' ] )
				{
					shuffle( $question[ 'data' ][ 'options' ] );
				}
				
				$Checkboxes = isset( $question[ 'data' ][ 'multiple_responses' ] ) && $question[ 'data' ][ 'multiple_responses' ];
				
				foreach( $question[ 'data' ][ 'options' ] as $key => $option )
				{
					if( $Checkboxes )
					{
						echo '<div class="checkbox"><label>';
						echo '<input type="checkbox" id="question_' . $questionid . '_answer_' . $key . '" name="question_' . $questionid . '_answer[]" value="' . $option[ 'value' ] . '">';
						echo ' ' . $option[ 'label' ];
						echo '</label></div>';
					}
					else
					{
						echo '<div class="radio"><label>';
						echo '<input type="radio" id="question_' . $questionid . '_answer_' . $key . '" name="question_' . $questionid . '_answer" value="' . $option[ 'value' ] . '">';
						echo ' ' . $option[ 'label' ];
						echo '</label></div>';
					}
				}
			}
			else if( $question[ 'type' ] === 'longtext' )
			{
				// Maximum number of words that can be entered in the field.
				
				echo '<textarea class="form-control" rows="4" name="question_' . $questionid . '_answer"></textarea>';
			}
			else if( $question[ 'type' ] === 'clozeassociation' )
			{
				$Responses = [ '<option selected="selected" value="-1"></option>' ];
				
				foreach( $question[ 'data' ][ 'possible_responses' ] as $Key => $Response )
				{
					$Responses[] = '<option value="' . $Key . '">' . $Response . '</option>';
				}
				
				$Responses = '<select id="question_' . $questionid . '_answer">' . implode( '', $Responses ) . '</select>';
				
				$Template = str_replace( '{{response}}', $Responses, $question[ 'data' ][ 'template' ] );
				
				echo $Template;
			}
			else {
				echo '<pre>';
				print_r($question);
				echo '</pre>';
			}
			
			echo '<hr>';
		}

		echo '<button type="submit" class="btn btn-primary">Answer</button></form>';
	}
}
