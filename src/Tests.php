<?php
namespace System;

class Tests
{
	public static function Render( $Request, $Response, $Service, $App )
	{
		$Tests = $App->Database->prepare( 'SELECT `TestID`, `Name`, `Tags`, (SELECT COUNT(*) FROM `tests_questions` WHERE `TestID` = `tests`.`TestID`) as `Size` FROM `tests` WHERE `UserID` = :userid ORDER BY `TestID` DESC' );
		$Tests->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
		$Tests->execute();
		$Tests = $Tests->fetchAll();
		
		$Tags = [];
		
		foreach( $Tests as &$Test )
		{
			if( empty( $Test->Tags ) )
			{
				continue;
			}
			
			$Test->Tags = explode( ',', $Test->Tags );
			
			foreach( $Test->Tags as $Tag )
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
		
		return $App->Twig->render( 'tests.html', [
			'title' => 'Tests Bank',
			'tab' => 'tests',
			'tests' => $Tests,
			'tags' => $Tags,
		] );
	}
	
	public static function RenderNewTest( $Request, $Response, $Service, $App )
	{
		$TestID = $Request->ID;
		$IsNewTest = $TestID < 1;
		
		if( !$IsNewTest )
		{
			$Test = $App->Database->prepare( 'SELECT `TestID`, `Tags`, `Name` FROM `tests` WHERE `UserID` = :userid AND `TestID` = :id' );
			$Test->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
			$Test->bindValue( ':id', $TestID, \PDO::PARAM_INT );
			$Test->execute();
			$Test = $Test->fetch();
			
			if( !$Test )
			{
				$Response->code( 404 );
				
				return 'Test not found';
			}
		}
		else
		{
			$Test = new \stdClass();
		}
		
		$Name = filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING );
		$Tags = filter_input( INPUT_POST, 'tags', FILTER_SANITIZE_STRING );
		
		$Questions = [];
		
		if( !empty( $_POST[ 'questions' ] ) && is_array( $_POST[ 'questions' ] ) )
		{
			$Questions = $_POST[ 'questions' ];
			
			foreach( $Questions as &$Question )
			{
				$Question = (int)$Question;
			}
		}
		
		if( !$IsNewTest && empty( $Questions ) )
		{
			$QuestionsGet = $App->Database->prepare( 'SELECT `QuestionID` FROM `tests_questions` WHERE `TestID` = :id ORDER BY `Order` ASC' );
			$QuestionsGet->bindValue( ':id', $TestID, \PDO::PARAM_INT );
			$QuestionsGet->execute();
			
			while( $Temp = $QuestionsGet->fetch() )
			{
				$Questions[] = (int)$Temp->QuestionID;
			}
		}
		
		$QuestionOrder = array_flip( $Questions );
		
		if( !empty( $Questions ) )
		{
			$Questions = $App->Database->prepare( 'SELECT `QuestionID`, `Type`, `Stimulus` FROM `questions` WHERE `UserID` = :userid AND `QuestionID` IN (' . implode( ', ', $Questions ) . ')' );
			$Questions->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
			$Questions->execute();
			$Questions = $Questions->fetchAll();
			
			usort( $Questions, function( $a, $b ) use( $QuestionOrder )
			{
				return $QuestionOrder[ $a->QuestionID ] - $QuestionOrder[ $b->QuestionID ];
			} ); 
		}
		
		if( isset( $_POST[ 'save' ] ) )
		{
			if( $IsNewTest )
			{
				$EditTest = $App->Database->prepare( 'INSERT INTO `tests` (`UserID`, `Name`, `Tags`) VALUES (:userid, :name, :tags)' );
				$EditTest->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
			}
			else
			{
				$EditTest = $App->Database->prepare( 'UPDATE `tests` SET `Name` = :name, `Tags` = :tags WHERE `UserID` = :userid AND `TestID` = :id' );
				$EditTest->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
				$EditTest->bindValue( ':id', $TestID, \PDO::PARAM_INT );
			}
			
			$EditTest->bindValue( ':name', $Name );
			$EditTest->bindValue( ':tags', $Tags );
			$EditTest->execute();
			
			if( $IsNewTest )
			{
				$Test = $App->Database->prepare( 'SELECT `TestID` FROM `tests` WHERE `UserID` = :userid ORDER BY `TestID` DESC LIMIT 1' );
				$Test->bindValue( ':userid', $_SESSION[ 'UserID' ], \PDO::PARAM_INT );
				$Test->execute();
				$Test = $Test->fetch();
				
				$TestID = $Test->TestID;
			}
			
			$InsertQuestion = $App->Database->prepare( 'DELETE FROM `tests_questions` WHERE `TestID` = :testid' );
			$InsertQuestion->bindValue( ':testid', $TestID, \PDO::PARAM_INT );
			$InsertQuestion->execute();
			
			if( !empty( $Questions ) )
			{
				$InsertQuestion = $App->Database->prepare( 'INSERT INTO `tests_questions` (`TestID`, `QuestionID`, `Order`) VALUES(:testid, :questionid, :order)' );
				$InsertQuestion->bindValue( ':testid', $TestID, \PDO::PARAM_INT );
				
				foreach( $Questions as $Question )
				{
					$InsertQuestion->bindValue( ':order', $QuestionOrder[ $Question->QuestionID ], \PDO::PARAM_INT );
					$InsertQuestion->bindValue( ':questionid', $Question->QuestionID, \PDO::PARAM_INT );
					$InsertQuestion->execute();
				}
			}
			
			if( $IsNewTest )
			{
				$Response->redirect( '/tests/edit/' . $TestID );
				return;
			}
			else if( $_POST[ 'save' ] === 'assign' )
			{
				$Response->redirect( '/assignments?testid=' . $TestID );
				return;
			}
		}
		
		if( !empty( $Name ) )
		{
			$Test->Name = $Name;
		}
		
		if( !empty( $Tags ) )
		{
			$Test->Tags = $Tags;
		}
		
		return $App->Twig->render( 'tests_new.html', [
			'title' => $IsNewTest ? 'Create New Test' : 'Edit Existing Test',
			'tab' => 'tests',
			'questions' => $Questions,
			'test' => $Test,
		] );
	}
}
