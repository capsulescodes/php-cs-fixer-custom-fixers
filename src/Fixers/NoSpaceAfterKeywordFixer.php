<?php

declare( strict_types = 1 );

namespace CapsulesCodes\PhpCsFixerCustomFixers\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;


final class NoSpaceAfterKeywordFixer extends AbstractFixer
{
	private const KEYWORD_KINDS = [ T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH, T_MATCH, T_CATCH, T_FUNCTION, T_FN ];


	public function getName() : string
	{
		return 'CapsulesCodes/no_space_after_keyword';
	}

	public function getDefinition() : FixerDefinitionInterface
	{
		return new FixerDefinition( 'There MUST be no space between a keyword and its opening parenthesis.', [] );
	}

	public function getPriority() : int
	{
		return 0;
	}

	public function isCandidate( Tokens $tokens ) : bool
	{
		return $tokens->isAnyTokenKindsFound( self::KEYWORD_KINDS );
	}

	protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
	{
		for( $index = 0, $count = count( $tokens ); $index < $count; ++$index )
		{
			if( ! $tokens[ $index ]->isGivenKind( self::KEYWORD_KINDS ) ) continue;

			$nextIndex = $index + 1;

			if( $nextIndex >= $count ) continue;

			if( ! $tokens[ $nextIndex ]->isWhitespace() ) continue;

			$afterWhitespace = $nextIndex + 1;

			if( $afterWhitespace >= $count ) continue;

			if( $tokens[ $afterWhitespace ]->getContent() !== '(' ) continue;

			$tokens->clearAt( $nextIndex );
		}
	}
}
