<?php

declare( strict_types = 1 );


namespace CapsulesCodes\Fixers;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;
use PhpCsFixer\Preg;
use PhpToken;

final class MethodChainingIndentationFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    public function getName() : string
    {
        return 'CapsulesCodes/method_chaining_indentation';
    }

    public function getDefinition() : FixerDefinitionInterface
    {
        return new FixerDefinition( 'Method chaining MUST be properly indented.', [] );
    }

    protected function createConfigurationDefinition() : FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver( [
            ( new FixerOptionBuilder( 'next-line', 'Set chains on next line' ) )->setAllowedValues( [ true, false ] )->setDefault(false )->getOption(),
            // ( new FixerOptionBuilder( 'vertical-align', 'Align chains vertically.' ) )->setAllowedValues( [ true, false ] )->setDefault( false )->getOption(),
        ] );
    }

    public function isCandidate( Tokens $tokens ) : bool
    {
        return $tokens->isAnyTokenKindsFound(  Token::getObjectOperatorKinds()  );
    }

    protected function applyFix( SplFileInfo $file, Tokens $tokens ) : void
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        for( $index = 1, $count = count( $tokens ); $index < $count; ++$index )
        {
            if( ! $tokens[ $index ]->isObjectOperator() ) continue;

            // if( $this->configuration[ 'vertical-align' ] && $tokens[ $index - 1 ]->isWhitespace() && $tokens[ $index - 2 ]->isGivenKind( T_VARIABLE ) ) $tokens->clearAt( $index - 1 );

            $endParenthesisIndex = $tokens->getNextTokenOfKind( $index, [ '(', ';', ',', [ T_CLOSE_TAG ] ] );

            if( null === $endParenthesisIndex || !$tokens[ $endParenthesisIndex ]->equals( '(' ) ) continue;

            if( $this->configuration[ 'next-line' ] || $this->canBeMovedToNextLine( $index, $tokens ) )
            {
                $newline = new Token( [ T_WHITESPACE, $lineEnding ] );

                if( $tokens[ $index - 1 ]->isWhitespace() )
                {
                    $tokens[ $index - 1 ] = $newline;
                }
                else
                {
                    $tokens->insertAt( $index, $newline );
                    ++$index;
                    ++$endParenthesisIndex;
                }
            }

            $currentIndent = $this->getIndentAt( $tokens, $index - 1 );

            if( null === $currentIndent ) continue;

            $expectedIndent = $this->getExpectedIndentAt( $tokens, $index );

            if( $currentIndent !== $expectedIndent ) $tokens[ $index - 1 ] = new Token( [ T_WHITESPACE, $lineEnding.$expectedIndent ] );

            $endParenthesisIndex = $tokens->findBlockEnd( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $endParenthesisIndex );

            for( $searchIndex = $index + 1; $searchIndex < $endParenthesisIndex; ++$searchIndex )
            {
                $searchToken = $tokens[ $searchIndex ];

                if( ! $searchToken->isWhitespace() ) continue;

                $content = $searchToken->getContent();

                if( ! Preg::match( '/\R/', $content ) ) continue;

                $content = Preg::replace( '/(\R)'.$currentIndent.'(\h*)$/D', '$1'.$expectedIndent.'$2', $content );

                $tokens[ $searchIndex ] = new Token( [ $searchToken->getId(), $content ] );
            }
        }
    }

    private function getExpectedIndentAt( Tokens $tokens, int $index ) : string
    {
        $index = $tokens->getPrevMeaningfulToken( $index );

        $indent = $this->whitespacesConfig->getIndent();

        // if( $this->configuration[ 'vertical-align' ] )
        // {
        //     $config = strlen( $indent );

        //     $length = 0 ;

        //     for( $i = $index; $i >= 0; --$i )
        //     {
        //         if( $tokens[ $i ]->isGivenKind( T_VARIABLE ) )
        //         {
        //             $length = strlen( $tokens[ $i ]->getContent() );

        //             break;
        //         }
        //     }

        //     $indent = str_repeat( $this->whitespacesConfig->getIndent(), intval( $length / $config ) ) . str_repeat( ' ', $length % $config );
        // }

        for( $l = $index; $l >= 0; --$l )
        {
            if( $tokens[ $l ]->equals( ')' ) ) $k = $tokens->findBlockStart( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $l );

            $currentIndent = $this->getIndentAt( $tokens, $l );

            if( null === $currentIndent ) continue;

            if( $this->currentLineRequiresExtraIndentLevel( $tokens, $l, $index ) ) return "{$currentIndent}{$indent}";

            return $currentIndent;
        }

        return $indent;
    }

    private function canBeMovedToNextLine( int $index, Tokens $tokens ) : bool
    {
        $prevMeaningful = $tokens->getPrevMeaningfulToken( $index );

        $hasCommentBefore = false;

        for( $i = $index - 1; $i > $prevMeaningful; --$i )
        {
            if( $tokens[ $i ]->isComment() )
            {
                $hasCommentBefore = true;

                continue;
            }

            if( $tokens[ $i ]->isWhitespace() && Preg::match( '/\R/', $tokens[ $i ]->getContent() ) ) return $hasCommentBefore;
        }

        return false;
    }

    private function getIndentAt( Tokens $tokens, int $index ) : string | null
    {
        if( Preg::match( '/\R{1}(\h*)$/', $this->getIndentContentAt( $tokens, $index ), $matches ) ) return $matches[1];

        return null;
    }

    private function getIndentContentAt( Tokens $tokens, int $index ) : string
    {
        if( !$tokens[ $index ]->isGivenKind( [ T_WHITESPACE, T_INLINE_HTML ] ) ) return '';

        $content = $tokens[ $index ]->getContent();

        if( $tokens[ $index ]->isWhitespace() && $tokens[ $index - 1 ]->isGivenKind( T_OPEN_TAG ) ) $content = $tokens[ $index - 1 ]->getContent().$content;

        if( Preg::match( '/\R/', $content ) ) return $content;

        return '';
    }

    private function currentLineRequiresExtraIndentLevel( Tokens $tokens, int $start, int $end ) : bool
    {
        $firstMeaningful = $tokens->getNextMeaningfulToken( $start );

        if($tokens[ $firstMeaningful ]->isObjectOperator() )
        {
            $thirdMeaningful = $tokens->getNextMeaningfulToken( $tokens->getNextMeaningfulToken( $firstMeaningful ) );

            return $tokens[ $thirdMeaningful ]->equals( '(' ) && $tokens->findBlockEnd( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $thirdMeaningful ) > $end;
        }

        return ! $tokens[ $end ]->equals( ')' ) || $tokens->findBlockStart( Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $end ) >= $start;
    }
}
