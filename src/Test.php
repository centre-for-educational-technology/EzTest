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
		//$xmlString = file_get_contents( 'examples/Tests/assessmentTest_SCORE.xml' );
		$xmlString = file_get_contents( '../qtifiles/choice2.xml' );
		list($item, $questions, $manifest) = Converter::convertQtiItemToLearnosity($xmlString);

		echo '<pre>';
		print_r($item);
		print_r($questions);
		print_r($manifest);
		echo '</pre>';

		$xmlString = file_get_contents( '../qtifiles/choice_multiple.xml' );
		$test = Converter::convertQtiItemToLearnosity($xmlString);
		$questions[1] = $test[1][0];


		echo '<script type="text/javascript" src="https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>';
		echo '<br><br><br>';

		$questionid = 0;

		echo '<form method="POST" class="container">';
		//echo '<h3>Item Title: ' . $item[ 'description' ] . '</h3>';

		// https://docs.learnosity.com/authoring/qti/index
		// https://docs.learnosity.com/assessment/questions/questiontypes#mcq
		foreach( $questions as $question )
		{
			$questionid++;
			
			if( $question[ 'type' ] === 'mcq' )
			{
				echo '<h3 class="text-muted">Question #' . $questionid . ':</h3><h4>' . $question[ 'data' ][ 'stimulus' ] . '</h4>';
				
				if( isset( $question[ 'data' ][ 'shuffle_options' ] ) && $question[ 'data' ][ 'shuffle_options' ] )
				{
					shuffle( $question[ 'data' ][ 'options' ] );
				}
				
				$Checkboxes = isset( $question[ 'data' ][ 'multiple_responses' ] ) && $question[ 'data' ][ 'multiple_responses' ];
				
				foreach( $question[ 'data' ][ 'options' ] as $key => $option )
				{
					echo '<div class="' . ( $Checkboxes ? 'checkbox' : 'radio' ) . '"><label>';
					echo '<input type="' . ( $Checkboxes ? 'checkbox' : 'radio' ) . '" id="question_' . $questionid . '_answer_' . $key . '" name="question_' . $questionid . '_answer' . ( $Checkboxes ? '[]' : '' ) . '" value="' . $option[ 'value' ] . '">';
					echo ' ' . $option[ 'label' ];
					echo '</label></div>';
				}
			}
			
			echo '<hr>';
		}

		echo '<button type="submit" class="btn btn-primary">Answer</button></form>';
	}
}
