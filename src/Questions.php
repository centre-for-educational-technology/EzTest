<?php
namespace System;

use LearnosityQti\Converter;

class Questions
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Questions = $App->Database->prepare( 'SELECT `QuestionID`, `Tags`, `Type`, `Stimulus` FROM `questions` WHERE `UserID` = :userid ORDER BY `Date` DESC' );
		$Questions->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Questions->execute();
		$Questions = $Questions->fetchAll();
		
		$Tags = [];
		
		foreach( $Questions as &$Question )
		{
			if( empty( $Question->Tags ) )
			{
				continue;
			}
			
			$Question->Tags = explode( ',', $Question->Tags );
			
			foreach( $Question->Tags as $Tag )
			{
				if( isset( $Tags[ $Tag ] ) )
				{
					$Tags[ $Tag ]++;
				}
				else
				{
					$Tags[ $Tag ] = 1;
				}
			}
		}
		
		arsort( $Tags );
		
		return $App->Twig->render( 'questions.html', [
			'title' => 'Questions Bank',
			'tab' => 'questions',
			'questions' => $Questions,
			'tags' => $Tags,
		] );
	}
	
	public static function HandleFileUpload( $Request, $Response, $Service, $App )
	{
		if( empty( $_FILES[ 'questions' ][ 'tmp_name' ] ) )
		{
			throw new \Exception( 'No files uploaded' );
		}
		
		$Files = $_FILES[ 'questions' ][ 'tmp_name' ];
		$Tags = isset( $_POST[ 'tags' ] ) ? $_POST[ 'tags' ] : '';
		
		$NewQuestion = $App->Database->prepare(
			'INSERT INTO `questions` (`Type`, `Tags`, `Stimulus`, `Data`, `Hash`, `UserID`) ' .
			'VALUES (:type, :tags, :stimulus, :data, :hash, :userid) ' .
			'ON DUPLICATE KEY UPDATE `Date` = NOW()'
		);
		
		foreach( $Files as $file )
		{
			echo 'Converting ' . $file . '<ol>';
			
			try
			{
				if( !is_uploaded_file( $file ) )
				{
					throw new \Exception( 'Not a file?' );
				}
				
				$xmlString = file_get_contents( $file );
				$converted = Converter::convertQtiItemToLearnosity( $xmlString );
				
				foreach( $converted[ 1 ] as $Data )
				{
					$Data = $Data[ 'data' ];
					$Type = $Data[ 'type' ];
					$Stimulus = isset( $Data[ 'stimulus' ] ) ? $Data[ 'stimulus' ] : '';
					
					// We calculate hash of full data object before removing stimulus and type
					$Hash = md5( json_encode( $Data ) );
					
					// We store stimulus and type in separate columns
					unset( $Data[ 'stimulus' ], $Data[ 'type' ] );
					
					$Data = json_encode( $Data );
					
					$NewQuestion->bindValue( ':tags', $Tags );
					$NewQuestion->bindValue( ':type', $Type );
					$NewQuestion->bindValue( ':stimulus', $Stimulus );
					$NewQuestion->bindValue( ':data', $Data );
					$NewQuestion->bindValue( ':hash', $Hash );
					$NewQuestion->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
					$NewQuestion->execute();
					
					unset( $Data, $Type, $Stimulus );
				}
				
				foreach( $converted[ 2 ] as $Data )
				{
					echo '<li>' . htmlentities( $Data ) . '</li>';
				}
			}
			catch( \Exception $e )
			{
				echo '<li>Failed to convert ' . $file . ' - ' . $e->getMessage() . '</li>';
			}
			
			echo '</ol><br>';
			echo '<a href="/questions">Go back</a>';
		}
	}
}
