<?php declare( strict_types = 1 );


namespace CapsulesCodes\PhpCsFixerCustomFixers\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;


final class SingleLineStatementFixer extends AbstractFixer
{
    private const KEYWORD_KINDS = [
        T_IF,
        T_WHILE,
        T_FOR,
        T_FOREACH,
    ];


    public function getName() : string
    {
        return 'CapsulesCodes/single_line_statement';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Single statement control structures MUST be on a single line without braces.', [] );
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
        for( $index = count( $tokens ) - 1; $index >= 0; --$index )
        {
            if( ! $tokens[ $index ]->isGivenKind( self::KEYWORD_KINDS ) ) continue;

            $openBraceIndex = $this->findOpenBrace( $tokens, $index );

            if( $openBraceIndex === null ) continue;

            $closeBraceIndex = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_CURLY_BRACE, $openBraceIndex );

            if( ! $this->isSingleStatement( $tokens, $openBraceIndex, $closeBraceIndex ) ) continue;

            if( $tokens[ $index ]->isGivenKind( T_IF ) && $this->hasElseBranch( $tokens, $closeBraceIndex ) ) continue;

            if( $tokens[ $closeBraceIndex - 1 ]->isWhitespace() ) $tokens->clearAt( $closeBraceIndex - 1 );

            $tokens->clearAt( $closeBraceIndex );

            if( $tokens[ $openBraceIndex + 1 ]->isWhitespace() ) $tokens->clearAt( $openBraceIndex + 1 );

            $tokens->clearAt( $openBraceIndex );

            if( $tokens[ $openBraceIndex - 1 ]->isWhitespace() )
            {
                $tokens[ $openBraceIndex - 1 ] = new Token( [ T_WHITESPACE, ' ' ] );
            }
        }
    }

    private function findOpenBrace( Tokens $tokens, int $index ) : int | null
    {
        $openParenIndex = $tokens->getNextMeaningfulToken( $index );

        if( $tokens[ $openParenIndex ]->getContent() !== '(' ) return null;

        $closeParenIndex = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openParenIndex );

        $nextMeaningful = $tokens->getNextMeaningfulToken( $closeParenIndex );

        if( $nextMeaningful === null || $tokens[ $nextMeaningful ]->getContent() !== '{' ) return null;

        return $nextMeaningful;
    }

    private function hasElseBranch( Tokens $tokens, int $closeBraceIndex ) : bool
    {
        $nextMeaningful = $tokens->getNextMeaningfulToken( $closeBraceIndex );

        if( $nextMeaningful === null ) return false;

        return $tokens[ $nextMeaningful ]->isGivenKind( [ T_ELSEIF, T_ELSE ] );
    }

    private function isSingleStatement( Tokens $tokens, int $openBrace, int $closeBrace ) : bool
    {
        $semicolons = 0;

        for( $i = $openBrace + 1; $i < $closeBrace; ++$i )
        {
            if( $tokens[ $i ]->isWhitespace() ) continue;

            if( $tokens[ $i ]->isComment() ) return false;

            if( $tokens[ $i ]->getContent() === ';' )
            {
                $semicolons++;

                continue;
            }

            if( $tokens[ $i ]->getContent() === '{' ) return false;

            if( $tokens[ $i ]->isGivenKind( self::KEYWORD_KINDS ) ) return false;
        }

        return $semicolons === 1;
    }
}
