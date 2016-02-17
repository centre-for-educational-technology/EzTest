<?php
namespace System;

use LearnosityQti\Converter;

class Questions
{
	public static function RenderQuestions( $Request, $Response, $Service, $App )
	{
		$Questions = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus` FROM `questions` WHERE `UserID` = :userid ORDER BY `Type`' );
		$Questions->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Questions->execute();
		$Questions = $Questions->fetchAll();
		
		return $App->Twig->render( 'questions.html', [
			'system_name' => \System\Config::$SystemName,
			'title' => 'Questions - ' . \System\Config::$SystemName,
			'tab' => 'questions',
			'questions' => $Questions,
		] );
	}
	
	public static function HandleFileUpload( $Request, $Response, $Service, $App )
	{
		echo '<pre>'; var_dump($_FILES);
		
		$files = $_FILES[ 'files' ];
		
		$questions = [];
		
		$NewQuestion = $App->Database->prepare(
			'INSERT INTO `questions` (`Type`, `Stimulus`, `Data`, `Hash`, `UserID`) ' .
			'VALUES (:type, :stimulus, :data, :hash, :userid)'
		);
		
		foreach( $files as $file )
		{
			try
			{
				var_dump($file);
				
				if( !is_uploaded_file( $file ) )
				{
					throw new \Exception( 'Not a file?' );
				}
				continue;
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
					$NewQuestion->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
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
}
